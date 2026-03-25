<?php

namespace FacturaScripts\Plugins\AnalisisLineas\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
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
        $data['title'] = 'Análisis de Líneas';
        $data['menu'] = 'informes';
        $data['submenu'] = 'Análisis Líneas';
        $data['icon'] = 'fas fa-chart-pie';
        $data['showonmenu'] = true;
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

        // Leer filtros de la request
        $this->fechaDesde = $this->request->get('fechadesde', date('Y-01-01'));
        $this->fechaHasta = $this->request->get('fechahasta', date('Y-m-d'));
        $this->codcliente = $this->request->get('codcliente', '');
        $this->codserie = $this->request->get('codserie', '');
        $this->clasificacion = $this->request->get('clasificacion', '');
        $this->busqueda = $this->request->get('busqueda', '');
        $this->pagina = (int) $this->request->get('pagina', 0);

        // Ejecutar análisis
        $this->ejecutarAnalisis();

        // Exportar CSV si se solicita
        if ($this->request->get('export') === 'csv') {
            $this->exportarCSV();
        }
    }

    /**
     * Ejecuta el análisis de líneas de facturas
     */
    private function ejecutarAnalisis(): void
    {
        $db = new DataBase();

        // Obtener palabras clave de clasificación
        $palabrasManoObra = $this->getPalabrasClave('mano_de_obra');
        $palabrasMaterial = $this->getPalabrasClave('material');

        // Construir consulta base
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

        $sql .= " ORDER BY f.fecha DESC, f.codigo, l.orden";

        // Obtener todas las líneas para el análisis global
        $todasLineas = $db->select($sql);

        // Clasificar cada línea
        $lineasClasificadas = [];
        $totalManoObra = 0;
        $totalMaterial = 0;
        $totalSinClasificar = 0;
        $countManoObra = 0;
        $countMaterial = 0;
        $countSinClasificar = 0;

        // Datos para gráfico mensual
        $mensual = [];

        foreach ($todasLineas as &$linea) {
            $tipo = $this->clasificarLinea($linea, $palabrasManoObra, $palabrasMaterial);
            $linea['clasificacion'] = $tipo;

            $pvptotal = (float) $linea['pvptotal'];

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

            // Agrupar por mes
            $mes = substr($linea['fecha'], 0, 7); // YYYY-MM
            if (!isset($mensual[$mes])) {
                $mensual[$mes] = ['mano_de_obra' => 0, 'material' => 0, 'sin_clasificar' => 0];
            }
            $mensual[$mes][$tipo] += $pvptotal;

            $lineasClasificadas[] = $linea;
        }

        // Filtrar por clasificación si se ha seleccionado
        if (!empty($this->clasificacion)) {
            $lineasClasificadas = array_filter($lineasClasificadas, function ($l) {
                return $l['clasificacion'] === $this->clasificacion;
            });
            $lineasClasificadas = array_values($lineasClasificadas);
        }

        // Resumen
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

        // Datos para gráfico de pastel
        $this->datosGrafico = [
            'mano_de_obra' => round($totalManoObra, 2),
            'material' => round($totalMaterial, 2),
            'sin_clasificar' => round($totalSinClasificar, 2),
        ];

        // Datos para gráfico mensual (ordenar por mes)
        ksort($mensual);
        $this->datosGraficoMensual = $mensual;

        // Paginación
        $totalLineas = count($lineasClasificadas);
        $this->totalPaginas = max(1, ceil($totalLineas / $this->porPagina));
        $this->pagina = max(0, min($this->pagina, $this->totalPaginas - 1));
        $this->resultados = array_slice($lineasClasificadas, $this->pagina * $this->porPagina, $this->porPagina);
    }

    /**
     * Clasifica una línea de factura como mano_de_obra, material o sin_clasificar
     */
    private function clasificarLinea(array $linea, array $palabrasManoObra, array $palabrasMaterial): string
    {
        $descripcion = mb_strtoupper($linea['descripcion'] ?? '', 'UTF-8');
        $pvpunitario = (float) ($linea['pvpunitario'] ?? 0);

        // 1. Primero comprobar palabras clave de mano de obra
        foreach ($palabrasManoObra as $palabra) {
            if (mb_strpos($descripcion, mb_strtoupper($palabra, 'UTF-8')) !== false) {
                return 'mano_de_obra';
            }
        }

        // 2. Comprobar palabras clave de material (si el usuario las ha definido)
        foreach ($palabrasMaterial as $palabra) {
            if (mb_strpos($descripcion, mb_strtoupper($palabra, 'UTF-8')) !== false) {
                return 'material';
            }
        }

        // 3. Heurística por precio unitario: precios redondos típicos de hora de trabajo
        $preciosHora = [15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80];
        if (in_array($pvpunitario, $preciosHora) && $pvpunitario == (int) $pvpunitario) {
            // Precio redondo típico de tarifa por hora
            return 'mano_de_obra';
        }

        // 4. Si tiene referencia de producto, probablemente es material
        if (!empty($linea['referencia'])) {
            return 'material';
        }

        // 5. Si no se puede clasificar
        return 'sin_clasificar';
    }

    /**
     * Obtiene las palabras clave activas de un tipo
     */
    private function getPalabrasClave(string $tipo): array
    {
        $items = LineaClasificacion::getByTipo($tipo);
        $palabras = [];
        foreach ($items as $item) {
            $palabras[] = $item->palabra_clave;
        }
        return $palabras;
    }

    /**
     * Carga la lista de clientes para el filtro
     */
    private function cargarClientes(): void
    {
        $db = new DataBase();
        $sql = "SELECT DISTINCT f.codcliente, f.nombrecliente"
            . " FROM facturascli f"
            . " WHERE f.codcliente IS NOT NULL"
            . " ORDER BY f.nombrecliente";
        $this->clientes = $db->select($sql);
    }

    /**
     * Carga las series para el filtro
     */
    private function cargarSeries(): void
    {
        $db = new DataBase();
        $sql = "SELECT codserie, descripcion FROM series ORDER BY codserie";
        $this->series = $db->select($sql);
    }

    /**
     * Exporta todas las líneas clasificadas a CSV
     */
    private function exportarCSV(): void
    {
        $db = new DataBase();
        $palabrasManoObra = $this->getPalabrasClave('mano_de_obra');
        $palabrasMaterial = $this->getPalabrasClave('material');

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

        $sql .= " ORDER BY f.fecha DESC, f.codigo, l.orden";
        $lineas = $db->select($sql);

        // Clasificar
        foreach ($lineas as &$linea) {
            $linea['clasificacion'] = $this->clasificarLinea($linea, $palabrasManoObra, $palabrasMaterial);
        }

        // Filtrar si hay clasificación seleccionada
        if (!empty($this->clasificacion)) {
            $lineas = array_filter($lineas, function ($l) {
                return $l['clasificacion'] === $this->clasificacion;
            });
        }

        // Generar CSV
        $filename = 'analisis_lineas_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // BOM para UTF-8 en Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabecera
        fputcsv($output, [
            'Factura', 'Fecha', 'Cod. Cliente', 'Cliente', 'Serie',
            'Descripción', 'Referencia', 'Cantidad', 'Precio Ud.',
            'Dto %', 'IVA %', 'Total Neto', 'Clasificación'
        ], ';');

        foreach ($lineas as $linea) {
            $tipoLabel = match ($linea['clasificacion']) {
                'mano_de_obra' => 'Mano de obra',
                'material' => 'Material',
                default => 'Sin clasificar',
            };

            fputcsv($output, [
                $linea['codigo'],
                $linea['fecha'],
                $linea['codcliente'],
                $linea['nombrecliente'],
                $linea['codserie'],
                $linea['descripcion'],
                $linea['referencia'],
                $linea['cantidad'],
                $linea['pvpunitario'],
                $linea['dtopor'],
                $linea['iva'],
                $linea['pvptotal'],
                $tipoLabel,
            ], ';');
        }

        fclose($output);
        exit();
    }

    public function getTemplate(): string
    {
        return 'AnalisisLineas.html.twig';
    }
}
