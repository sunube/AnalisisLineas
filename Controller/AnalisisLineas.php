<?php

namespace FacturaScripts\Plugins\AnalisisLineas\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\AnalisisLineas\Model\LineaClasificacion;

class AnalisisLineas extends Controller
{
    public $resultados = [];
    public $resumen = [];
    public $clientes = [];
    public $series = [];
    public $datosGrafico = [];
    public $datosGraficoMensual = [];
    public $categorias = [];
    public $fechaDesde = '';
    public $fechaHasta = '';
    public $codcliente = '';
    public $codserie = '';
    public $clasificacion = '';
    public $busqueda = '';
    public $pagina = 0;
    public $totalPaginas = 0;

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

        LineaClasificacion::seedDefaults();
        $this->categorias = LineaClasificacion::getCategorias();

        $this->cargarClientes();
        $this->cargarSeries();

        $this->fechaDesde = $this->request->query->get('fechadesde', date('Y-01-01'));
        $this->fechaHasta = $this->request->query->get('fechahasta', date('Y-m-d'));
        $this->codcliente = $this->request->query->get('codcliente', '');
        $this->codserie = $this->request->query->get('codserie', '');
        $this->clasificacion = $this->request->query->get('clasificacion', '');
        $this->busqueda = $this->request->query->get('busqueda', '');
        $this->pagina = (int) $this->request->query->get('pagina', 0);

        if ($this->request->query->get('export') === 'csv') {
            $this->exportarCSV();
            return;
        }

        $this->ejecutarAnalisis();
    }

    public function getTemplate(): string
    {
        return 'AnalisisLineas.html.twig';
    }

    private function ejecutarAnalisis(): void
    {
        $db = new DataBase();
        $keywordsAgrupadas = LineaClasificacion::getAllGrouped();

        $sql = $this->buildSQL($db);
        $todasLineas = $db->select($sql);
        if ($todasLineas === false) {
            $todasLineas = [];
        }

        $lineasClasificadas = [];
        $totalesPorCategoria = [];
        $countPorCategoria = [];
        $mensual = [];

        foreach ($this->categorias as $key => $cat) {
            $totalesPorCategoria[$key] = 0;
            $countPorCategoria[$key] = 0;
        }

        foreach ($todasLineas as $linea) {
            // Omitir lineas separadoras (--- Albaran, --- Pedido, etc.)
            if ($this->esSeparador($linea)) {
                continue;
            }

            $tipo = $this->clasificarLinea($linea, $keywordsAgrupadas);
            $linea['clasificacion'] = $tipo;
            $pvptotal = (float) ($linea['pvptotal'] ?? 0);

            if (isset($totalesPorCategoria[$tipo])) {
                $totalesPorCategoria[$tipo] += $pvptotal;
                $countPorCategoria[$tipo]++;
            }

            $mes = substr($linea['fecha'] ?? '', 0, 7);
            if (!empty($mes)) {
                if (!isset($mensual[$mes])) {
                    $mensual[$mes] = [];
                }
                if (!isset($mensual[$mes][$tipo])) {
                    $mensual[$mes][$tipo] = 0;
                }
                $mensual[$mes][$tipo] += $pvptotal;
            }

            $lineasClasificadas[] = $linea;
        }

        // Filtrar por clasificacion si se ha seleccionado
        if (!empty($this->clasificacion)) {
            $lineasClasificadas = array_values(array_filter($lineasClasificadas, function ($l) {
                return $l['clasificacion'] === $this->clasificacion;
            }));
        }

        // Resumen
        $totalGeneral = array_sum($totalesPorCategoria);
        $countTotal = array_sum($countPorCategoria);
        $this->resumen = [
            'total_general' => $totalGeneral,
            'count_total' => $countTotal,
            'por_categoria' => [],
        ];

        foreach ($this->categorias as $key => $cat) {
            if ($countPorCategoria[$key] > 0 || $key === 'sin_clasificar') {
                $this->resumen['por_categoria'][$key] = [
                    'total' => $totalesPorCategoria[$key],
                    'count' => $countPorCategoria[$key],
                    'pct' => $totalGeneral > 0 ? round($totalesPorCategoria[$key] / $totalGeneral * 100, 1) : 0,
                ];
            }
        }

        // Datos para grafico
        $this->datosGrafico = [];
        foreach ($this->categorias as $key => $cat) {
            if ($totalesPorCategoria[$key] > 0) {
                $this->datosGrafico[$key] = round($totalesPorCategoria[$key], 2);
            }
        }

        ksort($mensual);
        $this->datosGraficoMensual = $mensual;

        // Paginacion
        $totalLineas = count($lineasClasificadas);
        $this->totalPaginas = max(1, (int) ceil($totalLineas / $this->porPagina));
        $this->pagina = max(0, min($this->pagina, $this->totalPaginas - 1));
        $this->resultados = array_slice($lineasClasificadas, $this->pagina * $this->porPagina, $this->porPagina);
    }

    /**
     * Detecta si una linea es un separador (--- Albaran, --- Pedido, etc.)
     */
    private function esSeparador(array $linea): bool
    {
        $desc = trim($linea['descripcion'] ?? '');
        $cantidad = (float) ($linea['cantidad'] ?? 0);
        $precio = (float) ($linea['pvpunitario'] ?? 0);

        // Lineas que empiezan por "---" son separadores
        if (str_starts_with($desc, '---')) {
            return true;
        }

        // Lineas con cantidad 0, precio 0 y total 0
        if ($cantidad == 0 && $precio == 0 && (float)($linea['pvptotal'] ?? 0) == 0) {
            return true;
        }

        return false;
    }

    /**
     * Clasifica una linea recorriendo las categorias por orden de prioridad
     */
    private function clasificarLinea(array $linea, array $keywordsAgrupadas): string
    {
        $descripcion = $this->normalizarTexto($linea['descripcion'] ?? '');

        // Primero evaluar categorias especificas de producto/servicio
        // y DESPUES mano_de_obra (que tiene keywords genericas como INSTALACION, CONFIGURACION
        // que pueden aparecer en descripciones de productos)
        $prioridad = [
            'licencia_microsoft', 'antivirus', 'dominio', 'hosting',
            'camara', 'centralita', 'telefonia', 'servidor_cloud', 'correo',
            'firma_digital', 'internet', 'impresora', 'software', 'consultoria',
            'diseno_web', 'monitor', 'periferico', 'cable_red', 'componente', 'hardware',
            'mano_de_obra',
        ];

        foreach ($prioridad as $tipo) {
            if (!isset($keywordsAgrupadas[$tipo])) {
                continue;
            }
            foreach ($keywordsAgrupadas[$tipo] as $palabra) {
                if (mb_strpos($descripcion, $this->normalizarTexto($palabra)) !== false) {
                    return $tipo;
                }
            }
        }

        // Si tiene referencia de producto pero no encaja en ninguna categoria
        if (!empty($linea['referencia'])) {
            return 'hardware';
        }

        return 'sin_clasificar';
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtoupper($texto, 'UTF-8');
        $acentos = ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü', 'À', 'È', 'Ì', 'Ò', 'Ù'];
        $sinAcentos = ['A', 'E', 'I', 'O', 'U', 'N', 'U', 'A', 'E', 'I', 'O', 'U'];
        return str_replace($acentos, $sinAcentos, $texto);
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

    private function cargarClientes(): void
    {
        $db = new DataBase();
        $sql = "SELECT DISTINCT f.codcliente, f.nombrecliente FROM facturascli f"
            . " WHERE f.codcliente IS NOT NULL ORDER BY f.nombrecliente";
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

    private function exportarCSV(): void
    {
        $db = new DataBase();
        $keywordsAgrupadas = LineaClasificacion::getAllGrouped();
        $categorias = LineaClasificacion::getCategorias();

        $sql = $this->buildSQL($db);
        $lineas = $db->select($sql);
        if ($lineas === false) {
            $lineas = [];
        }

        $lineasFiltradas = [];
        foreach ($lineas as $linea) {
            if ($this->esSeparador($linea)) {
                continue;
            }
            $tipo = $this->clasificarLinea($linea, $keywordsAgrupadas);
            $linea['clasificacion'] = $tipo;

            if (!empty($this->clasificacion) && $tipo !== $this->clasificacion) {
                continue;
            }
            $lineasFiltradas[] = $linea;
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

        foreach ($lineasFiltradas as $linea) {
            $tipoLabel = $categorias[$linea['clasificacion']]['label'] ?? $linea['clasificacion'];

            fputcsv($output, [
                $linea['codigo'] ?? '', $linea['fecha'] ?? '',
                $linea['codcliente'] ?? '', $linea['nombrecliente'] ?? '',
                $linea['codserie'] ?? '', $linea['descripcion'] ?? '',
                $linea['referencia'] ?? '', $linea['cantidad'] ?? 0,
                $linea['pvpunitario'] ?? 0, $linea['dtopor'] ?? 0,
                $linea['iva'] ?? 0, $linea['pvptotal'] ?? 0, $tipoLabel,
            ], ';');
        }

        fclose($output);
        $this->setTemplate(false);
    }
}
