<?php

namespace FacturaScripts\Plugins\AnalisisLineas\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\AnalisisLineas\Model\LineaClasificacion;

class AnalisisLineas extends Controller
{
    /** @var array */
    public $resultados = [];

    /** @var array */
    public $resumen = [];

    /** @var array */
    public $clientes = [];

    /** @var array */
    public $series = [];

    /** @var array */
    public $datosGrafico = [];

    /** @var array */
    public $datosGraficoMensual = [];

    /** @var string */
    public $fechaDesde = '';

    /** @var string */
    public $fechaHasta = '';

    /** @var string */
    public $codcliente = '';

    /** @var string */
    public $codserie = '';

    /** @var string */
    public $clasificacion = '';

    /** @var string */
    public $busqueda = '';

    /** @var int */
    public $pagina = 0;

    /** @var int */
    public $totalPaginas = 0;

    /** @var int */
    private $porPagina = 50;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['name'] = 'AnalisisLineas';
        $data['title'] = 'analisis-lineas';
        $data['menu'] = 'reports';
        $data['icon'] = 'fa-solid fa-chart-pie';
        $data['showonmenu'] = true;
        $data['ordernum'] = 50;
        return $data;
    }

    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);

        // Semilla de palabras clave por defecto
        LineaClasificacion::seedDefaults();

        // Cargar listas para filtros
        $this->cargarClientes();
        $this->cargarSeries();

        $db = new DataBase();

        // Leer filtros de la request
        $this->fechaDesde = $this->request->query->get('fechadesde', date('Y-01-01'));
        $this->fechaHasta = $this->request->query->get('fechahasta', date('Y-m-d'));
        $this->codcliente = $this->request->query->get('codcliente', '');
        $this->codserie = $this->request->query->get('codserie', '');
        $this->clasificacion = $this->request->query->get('clasificacion', '');
        $this->busqueda = $this->request->query->get('busqueda', '');
        $this->pagina = (int) $this->request->query->get('pagina', 0);

        // Exportar CSV si se solicita
        if ($this->request->query->get('export') === 'csv') {
            $this->exportarCSV($db);
            return;
        }

        // Ejecutar análisis
        $this->ejecutarAnalisis($db);
    }

    public function getTemplate(): string
    {
        return 'AnalisisLineas.html.twig';
    }

    private function ejecutarAnalisis(DataBase $db): void
    {
        $palabrasManoObra = $this->getPalabrasClave('mano_de_obra');
        $palabrasMaterial = $this->getPalabrasClave('material');

        $sql = $this->buildSQL($db);
        $todasLineas = $db->select($sql);

        if ($todasLineas === false) {
            $todasLineas = [];
        }

        $lineasClasificadas = [];
        $totalManoObra = 0;
        $totalMaterial = 0;
        $totalSinClasificar = 0;
        $countManoObra = 0;
        $countMaterial = 0;
        $countSinClasificar = 0;
        $mensual = [];

        foreach ($todasLineas as $linea) {
            $tipo = $this->clasificarLinea($linea, $palabrasManoObra, $palabrasMaterial);
            $linea['clasificacion'] = $tipo;
            $pvptotal = (float) ($linea['pvptotal'] ?? 0);

            switch ($tipo) {
                case 'mano_de_obra':
                    $totalManoObra += $pvptotal;
                    $countManoObra++;
                    break;
                case 'material':
                    $totalMaterial += $pvptotal;
                    $countMaterial++;
                    break;
                default:
                    $totalSinClasificar += $pvptotal;
                    $countSinClasificar++;
                    break;
            }

            $mes = substr($linea['fecha'] ?? '', 0, 7);
            if (!isset($mensual[$mes])) {
                $mensual[$mes] = ['mano_de_obra' => 0, 'material' => 0, 'sin_clasificar' => 0];
            }
            $mensual[$mes][$tipo] += $pvptotal;

            $lineasClasificadas[] = $linea;
        }

        // Filtrar por clasificación si se ha seleccionado
        if (!empty($this->clasificacion)) {
            $lineasClasificadas = array_values(array_filter($lineasClasificadas, function ($l) {
                return $l['clasificacion'] === $this->clasificacion;
            }));
        }

        $totalGeneral = $totalManoObra + $totalMaterial + $totalSinClasificar;
        $this->resumen = [
            'total_mano_obra' => $totalManoObra,
            'total_material' => $totalMaterial,
            'total_sin_clasificar' => $totalSinClasificar,
            'total_general' => $totalGeneral,
            'count_mano_obra' => $countManoObra,
            'count_material' => $countMaterial,
            'count_sin_clasificar' => $countSinClasificar,
            'count_total' => count($todasLineas),
            'pct_mano_obra' => $totalGeneral > 0 ? round($totalManoObra / $totalGeneral * 100, 1) : 0,
            'pct_material' => $totalGeneral > 0 ? round($totalMaterial / $totalGeneral * 100, 1) : 0,
            'pct_sin_clasificar' => $totalGeneral > 0 ? round($totalSinClasificar / $totalGeneral * 100, 1) : 0,
        ];

        $this->datosGrafico = [
            'mano_de_obra' => round($totalManoObra, 2),
            'material' => round($totalMaterial, 2),
            'sin_clasificar' => round($totalSinClasificar, 2),
        ];

        ksort($mensual);
        $this->datosGraficoMensual = $mensual;

        $totalLineas = count($lineasClasificadas);
        $this->totalPaginas = max(1, (int) ceil($totalLineas / $this->porPagina));
        $this->pagina = max(0, min($this->pagina, $this->totalPaginas - 1));
        $this->resultados = array_slice($lineasClasificadas, $this->pagina * $this->porPagina, $this->porPagina);
    }

    private function buildSQL(DataBase $db): string
    {
        $sql = "SELECT l.idlinea, l.descripcion, l.cantidad, l.pvpunitario, l.pvpsindto, l.pvptotal,"
            . " l.dtopor, l.iva, l.referencia,"
            . " f.idfactura, f.codigo, f.fecha, f.codcliente, f.nombrecliente, f.codserie, f.total"
            . " FROM lineasfacturascli l"
            . " LEFT JOIN facturascli f ON l.idfactura = f.idfactura"
            . " WHERE f.fecha >= " . $db->var2str($this->fechaDesde)
            . " AND f.fecha <= " . $db->var2str($this->fechaHasta);

        if (!empty($this->codcliente)) {
            $sql .= " AND f.codcliente = " . $db->var2str($this->codcliente);
        }
        if (!empty($this->codserie)) {
            $sql .= " AND f.codserie = " . $db->var2str($this->codserie);
        }
        if (!empty($this->busqueda)) {
            $sql .= " AND LOWER(l.descripcion) LIKE LOWER(" . $db->var2str('%' . $this->busqueda . '%') . ")";
        }

        $sql .= " ORDER BY f.fecha DESC, f.codigo";
        return $sql;
    }

    private function clasificarLinea(array $linea, array $palabrasManoObra, array $palabrasMaterial): string
    {
        $descripcion = $this->normalizarTexto($linea['descripcion'] ?? '');
        $pvpunitario = (float) ($linea['pvpunitario'] ?? 0);
        $cantidad = (float) ($linea['cantidad'] ?? 0);

        // Si la linea tiene importe 0 y cantidad 0, es una linea de texto/separador
        if ($pvpunitario == 0 && $cantidad == 0) {
            return 'sin_clasificar';
        }

        // 1. Comprobar palabras clave de mano de obra en la descripcion
        foreach ($palabrasManoObra as $palabra) {
            if (mb_strpos($descripcion, $this->normalizarTexto($palabra)) !== false) {
                return 'mano_de_obra';
            }
        }

        // 2. Comprobar palabras clave de material
        foreach ($palabrasMaterial as $palabra) {
            if (mb_strpos($descripcion, $this->normalizarTexto($palabra)) !== false) {
                return 'material';
            }
        }

        // 3. Si tiene referencia de producto, es material
        if (!empty($linea['referencia'])) {
            return 'material';
        }

        // 4. No se puede clasificar automaticamente
        return 'sin_clasificar';
    }

    /**
     * Normaliza texto: mayusculas y sin tildes/acentos para comparar
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtoupper($texto, 'UTF-8');
        $acentos = ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü', 'À', 'È', 'Ì', 'Ò', 'Ù'];
        $sinAcentos = ['A', 'E', 'I', 'O', 'U', 'N', 'U', 'A', 'E', 'I', 'O', 'U'];
        return str_replace($acentos, $sinAcentos, $texto);
    }

    private function getPalabrasClave(string $tipo): array
    {
        $items = LineaClasificacion::getByTipo($tipo);
        $palabras = [];
        foreach ($items as $item) {
            $palabras[] = $item->palabra_clave;
        }
        return $palabras;
    }

    private function cargarClientes(): void
    {
        $db = new DataBase();
        $sql = "SELECT DISTINCT f.codcliente, f.nombrecliente"
            . " FROM facturascli f"
            . " WHERE f.codcliente IS NOT NULL"
            . " ORDER BY f.nombrecliente";
        $result = $db->select($sql);
        $this->clientes = $result !== false ? $result : [];
    }

    private function cargarSeries(): void
    {
        $db = new DataBase();
        $sql = "SELECT codserie, descripcion FROM series ORDER BY codserie";
        $result = $db->select($sql);
        $this->series = $result !== false ? $result : [];
    }

    private function exportarCSV(DataBase $db): void
    {
        $palabrasManoObra = $this->getPalabrasClave('mano_de_obra');
        $palabrasMaterial = $this->getPalabrasClave('material');

        $sql = $this->buildSQL($db);
        $lineas = $db->select($sql);
        if ($lineas === false) {
            $lineas = [];
        }

        foreach ($lineas as &$linea) {
            $linea['clasificacion'] = $this->clasificarLinea($linea, $palabrasManoObra, $palabrasMaterial);
        }

        if (!empty($this->clasificacion)) {
            $lineas = array_filter($lineas, function ($l) {
                return $l['clasificacion'] === $this->clasificacion;
            });
        }

        $filename = 'analisis_lineas_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            'Factura', 'Fecha', 'Cod. Cliente', 'Cliente', 'Serie',
            'Descripcion', 'Referencia', 'Cantidad', 'Precio Ud.',
            'Dto %', 'IVA %', 'Total Neto', 'Clasificacion'
        ], ';');

        foreach ($lineas as $linea) {
            $tipoLabel = match ($linea['clasificacion']) {
                'mano_de_obra' => 'Mano de obra',
                'material' => 'Material',
                default => 'Sin clasificar',
            };

            fputcsv($output, [
                $linea['codigo'] ?? '',
                $linea['fecha'] ?? '',
                $linea['codcliente'] ?? '',
                $linea['nombrecliente'] ?? '',
                $linea['codserie'] ?? '',
                $linea['descripcion'] ?? '',
                $linea['referencia'] ?? '',
                $linea['cantidad'] ?? 0,
                $linea['pvpunitario'] ?? 0,
                $linea['dtopor'] ?? 0,
                $linea['iva'] ?? 0,
                $linea['pvptotal'] ?? 0,
                $tipoLabel,
            ], ';');
        }

        fclose($output);
        $this->setTemplate(false);
    }
}
