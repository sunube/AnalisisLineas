<?php

namespace FacturaScripts\Plugins\AnalisisLineas\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

class LineaClasificacion extends ModelClass
{
    use ModelTrait;

    /** @var int */
    public $id;

    /** @var string */
    public $tipo;

    /** @var string */
    public $palabra_clave;

    /** @var bool */
    public $activo;

    /** @var string */
    public $creacion;

    /** @var int Orden de prioridad (menor = se evalua primero) */
    public $orden;

    /**
     * Categorias disponibles con etiqueta, color e icono
     */
    public static function getCategorias(): array
    {
        return [
            'mano_de_obra' => ['label' => 'Mano de obra', 'color' => '#198754', 'icon' => 'fa-solid fa-wrench'],
            'telefonia' => ['label' => 'Telefonia', 'color' => '#0dcaf0', 'icon' => 'fa-solid fa-phone'],
            'licencia_microsoft' => ['label' => 'Licencias Microsoft', 'color' => '#0078d4', 'icon' => 'fa-solid fa-window-maximize'],
            'hardware' => ['label' => 'Hardware', 'color' => '#6f42c1', 'icon' => 'fa-solid fa-laptop'],
            'servidor_cloud' => ['label' => 'Servidores / Cloud', 'color' => '#20c997', 'icon' => 'fa-solid fa-cloud'],
            'cable_red' => ['label' => 'Cables / Red', 'color' => '#fd7e14', 'icon' => 'fa-solid fa-network-wired'],
            'internet' => ['label' => 'Internet / Conectividad', 'color' => '#0d6efd', 'icon' => 'fa-solid fa-globe'],
            'componente' => ['label' => 'Componentes', 'color' => '#e83e8c', 'icon' => 'fa-solid fa-microchip'],
            'periferico' => ['label' => 'Perifericos', 'color' => '#6610f2', 'icon' => 'fa-solid fa-keyboard'],
            'monitor' => ['label' => 'Monitores', 'color' => '#495057', 'icon' => 'fa-solid fa-desktop'],
            'impresora' => ['label' => 'Impresoras / Consumibles', 'color' => '#dc3545', 'icon' => 'fa-solid fa-print'],
            'correo' => ['label' => 'Correo / Email', 'color' => '#ffc107', 'icon' => 'fa-solid fa-envelope'],
            'firma_digital' => ['label' => 'Firma digital', 'color' => '#795548', 'icon' => 'fa-solid fa-file-signature'],
            'centralita' => ['label' => 'Centralita / VoIP', 'color' => '#607d8b', 'icon' => 'fa-solid fa-headset'],
            'dominio' => ['label' => 'Dominios', 'color' => '#ff5722', 'icon' => 'fa-solid fa-at'],
            'hosting' => ['label' => 'Hosting', 'color' => '#4caf50', 'icon' => 'fa-solid fa-server'],
            'antivirus' => ['label' => 'Antivirus', 'color' => '#f44336', 'icon' => 'fa-solid fa-shield-halved'],
            'camara' => ['label' => 'Camaras / CCTV', 'color' => '#9c27b0', 'icon' => 'fa-solid fa-video'],
            'software' => ['label' => 'Software / Licencias', 'color' => '#3f51b5', 'icon' => 'fa-solid fa-compact-disc'],
            'consultoria' => ['label' => 'Consultoria / Asesoria', 'color' => '#009688', 'icon' => 'fa-solid fa-user-tie'],
            'diseno_web' => ['label' => 'Diseno Web / Marketing', 'color' => '#e91e63', 'icon' => 'fa-solid fa-palette'],
            'sin_clasificar' => ['label' => 'Sin clasificar', 'color' => '#6c757d', 'icon' => 'fa-solid fa-question'],
        ];
    }

    public function clear(): void
    {
        parent::clear();
        $this->tipo = 'mano_de_obra';
        $this->activo = true;
        $this->creacion = date('Y-m-d H:i:s');
        $this->orden = 10;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'lineas_clasificacion';
    }

    public function test(): bool
    {
        $this->palabra_clave = Tools::noHtml($this->palabra_clave ?? '');
        if (empty($this->palabra_clave)) {
            Tools::log()->warning('La palabra clave no puede estar vacia.');
            return false;
        }
        return parent::test();
    }

    public static function getByTipo(string $tipo): array
    {
        $model = new static();
        $where = [
            new DataBaseWhere('tipo', $tipo),
            new DataBaseWhere('activo', true),
        ];
        return $model->all($where, ['orden' => 'ASC'], 0, 0);
    }

    public static function getAllActive(): array
    {
        $model = new static();
        $where = [
            new DataBaseWhere('activo', true),
        ];
        return $model->all($where, ['orden' => 'ASC', 'tipo' => 'ASC'], 0, 0);
    }

    /**
     * Devuelve todas las keywords agrupadas por tipo, ordenadas por prioridad
     */
    public static function getAllGrouped(): array
    {
        $all = static::getAllActive();
        $grouped = [];
        foreach ($all as $item) {
            $grouped[$item->tipo][] = $item->palabra_clave;
        }
        return $grouped;
    }

    public static function seedDefaults(): void
    {
        $defaults = [
            // === MANO DE OBRA (se evalua ULTIMO en el clasificador) ===
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'INSTALACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'CONFIGURACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PUESTA EN MARCHA', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'MANO DE OBRA', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'MONTAJE', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PROGRAMACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'DESPLAZAMIENTO', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'SERVICIO TECNICO', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'FORMACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'MANTENIMIENTO', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'REPARACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'ASISTENCIA', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'SOPORTE', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'FORMATEO', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'RECUPERACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PREPARAR', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PROBLEMA CON', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'CALIBRACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'DIAGNOSTICO', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'REVISION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'MIGRACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'RESTAURACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'CAMBIO DE PLACA', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PRECONFIGURAC', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PRESTACION DE SERVICIOS', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'CONTRATO DE SERVICIOS', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PROBLEMAS CON', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PROBLEMA DE', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PROBLEMAS DE', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'COMPROBACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'COMPROBACIONES', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'ACTIVACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'ACTUALIZACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'INSTALAR', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'LIMPIEZA', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'RECOGIDA', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'CAMBIO DE', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'TRASPASO DE DATOS', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'SACAR COPIA', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PONER COPIA', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'SOLUCIONAR', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'DESINSTALAR', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'REPLANTEO', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'CONFLICTO', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'EXPORTACION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'CONECTAR', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'REUNION', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'METER ARTICULOS', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'MIGRAR DATOS', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'FORMATEAR', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'HORAS EN WEB', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'FALLO EN', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PRUEBAS CON', 'orden' => 1],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'MATENIMIENTO', 'orden' => 1],

            // === LICENCIAS MICROSOFT (orden 2) ===
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'MICROSOFT 365', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'OFFICE 365', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'OFFICE 2024', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'POWER BI', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'LICENCIA OFFICE', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'WINDOWS PRO', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'LICENCIA WINDOWS', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'LIECENCIA WINDOWS', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'SHAREPOINT', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'PROJECT PLAN', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'MICROSOFT TEAMS', 'orden' => 2],
            ['tipo' => 'licencia_microsoft', 'palabra_clave' => 'MICROSOFT E3', 'orden' => 2],

            // === ANTIVIRUS (orden 3) ===
            ['tipo' => 'antivirus', 'palabra_clave' => 'ANTIVIRUS', 'orden' => 3],
            ['tipo' => 'antivirus', 'palabra_clave' => 'KASPERSKY', 'orden' => 3],
            ['tipo' => 'antivirus', 'palabra_clave' => 'KASPESRKY', 'orden' => 3],
            ['tipo' => 'antivirus', 'palabra_clave' => 'BITDEFENDER', 'orden' => 3],
            ['tipo' => 'antivirus', 'palabra_clave' => 'FORTIGATE', 'orden' => 3],

            // === DOMINIOS (orden 4) ===
            ['tipo' => 'dominio', 'palabra_clave' => 'DOMINIO', 'orden' => 4],

            // === HOSTING (orden 5) ===
            ['tipo' => 'hosting', 'palabra_clave' => 'HOSTING', 'orden' => 5],
            ['tipo' => 'hosting', 'palabra_clave' => 'ALOJAMIENTO WEB', 'orden' => 5],

            // === CAMARAS / CCTV (orden 6) ===
            ['tipo' => 'camara', 'palabra_clave' => 'CAMARA IP', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'CAMARA DOMO', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'CAMARA BULLET', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'CAMARA DAHUA', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'CAMARA TAPO', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'CAMARA TENVEO', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'GRABADOR', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'NVR', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'DVR', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'WIZSENSE', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'WIZCOLOR', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'CAJA DE CONEXIONES CAMARA', 'orden' => 6],
            ['tipo' => 'camara', 'palabra_clave' => 'VIDEOVIGILANCIA', 'orden' => 6],

            // === CENTRALITA / VOIP (orden 7) ===
            ['tipo' => 'centralita', 'palabra_clave' => 'CENTRALITA', 'orden' => 7],
            ['tipo' => 'centralita', 'palabra_clave' => 'VOIP', 'orden' => 7],
            ['tipo' => 'centralita', 'palabra_clave' => 'TELEFONO IP', 'orden' => 7],
            ['tipo' => 'centralita', 'palabra_clave' => 'TELEFONOS VOIP', 'orden' => 7],

            // === TELEFONIA (orden 8) ===
            ['tipo' => 'telefonia', 'palabra_clave' => 'LLAMADAS ILIMITADAS', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'LINEA M2M', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'MOVIL ILIMITADAS', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'TELEFONIA MOVIL', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'CONSUMO TELEFONIA', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'TARIFA MOVIL', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'GB Y LLAMADAS', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'SOLO VOZ', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'AMPLIACION DE TARIFA', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'EXCESO CONSUMO', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'EXCESO DE CONSUMO', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'ILIMITADAS', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'LINEA MOVIL', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'LINEA DE TELEFONO', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'LINEA TELEFONO', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'GIGAS', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'TARIFA 10GB', 'orden' => 8],
            ['tipo' => 'telefonia', 'palabra_clave' => 'PORTABILIDAD', 'orden' => 8],

            // === SERVIDOR CLOUD / BACKUP (orden 9) ===
            ['tipo' => 'servidor_cloud', 'palabra_clave' => 'SERVIDOR CLOUD', 'orden' => 9],
            ['tipo' => 'servidor_cloud', 'palabra_clave' => 'CLOUD BACKUP', 'orden' => 9],
            ['tipo' => 'servidor_cloud', 'palabra_clave' => 'CLOUD STORAGE', 'orden' => 9],
            ['tipo' => 'servidor_cloud', 'palabra_clave' => 'COPIA DE SEGURIDAD', 'orden' => 9],
            ['tipo' => 'servidor_cloud', 'palabra_clave' => 'BACKUP', 'orden' => 9],
            ['tipo' => 'servidor_cloud', 'palabra_clave' => 'AMPLIACION SERVIDOR', 'orden' => 9],
            ['tipo' => 'servidor_cloud', 'palabra_clave' => 'PLATAFORMA CLOUD', 'orden' => 9],
            ['tipo' => 'servidor_cloud', 'palabra_clave' => 'SOFTWARE TELETRABAJO', 'orden' => 9],

            // === CORREO / EMAIL (orden 10) ===
            ['tipo' => 'correo', 'palabra_clave' => 'CORREO GSUITE', 'orden' => 10],
            ['tipo' => 'correo', 'palabra_clave' => 'CORREO GOOGLE', 'orden' => 10],
            ['tipo' => 'correo', 'palabra_clave' => 'GOOGLE WORKSPACE', 'orden' => 10],
            ['tipo' => 'correo', 'palabra_clave' => 'SERVICIO DE CORREO', 'orden' => 10],
            ['tipo' => 'correo', 'palabra_clave' => 'SERVICIOS CORREO', 'orden' => 10],
            ['tipo' => 'correo', 'palabra_clave' => 'AMPLIACION EMAIL', 'orden' => 10],
            ['tipo' => 'correo', 'palabra_clave' => 'GB EMAIL', 'orden' => 10],
            ['tipo' => 'correo', 'palabra_clave' => 'AMPLIACION DE 10GB', 'orden' => 10],

            // === FIRMA DIGITAL (orden 11) ===
            ['tipo' => 'firma_digital', 'palabra_clave' => 'SERVICIOS FIRMA', 'orden' => 11],
            ['tipo' => 'firma_digital', 'palabra_clave' => 'SERVICIOS DE FIRMA', 'orden' => 11],
            ['tipo' => 'firma_digital', 'palabra_clave' => 'FIRMA DIGITAL', 'orden' => 11],
            ['tipo' => 'firma_digital', 'palabra_clave' => 'CERTIFICADO DIGITAL', 'orden' => 11],

            // === INTERNET / CONECTIVIDAD (orden 12) ===
            ['tipo' => 'internet', 'palabra_clave' => 'SERVICIO DNS', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'IP FIJA', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'FIBRA', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'SERVICIO 20/', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'SERVICIO 30/', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'SERVICIO 50/', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'SERVICIO 100/', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'SERVICIO 300/', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'SERVICIO 600/', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'ADSL', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'CONEXION A INTERNET', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'CONEXION INTERNET', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'CUOTA INTERNET', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'INTERNET 100', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'INTERNET 15', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'INTERNET 20', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'INTERNET 30', 'orden' => 12],
            ['tipo' => 'internet', 'palabra_clave' => 'INTERNET 50', 'orden' => 12],

            // === IMPRESORAS / CONSUMIBLES (orden 13) ===
            ['tipo' => 'impresora', 'palabra_clave' => 'IMPRESORA', 'orden' => 13],
            ['tipo' => 'impresora', 'palabra_clave' => 'TONER', 'orden' => 13],
            ['tipo' => 'impresora', 'palabra_clave' => 'TAMBOR', 'orden' => 13],
            ['tipo' => 'impresora', 'palabra_clave' => 'CARTUCHO', 'orden' => 13],
            ['tipo' => 'impresora', 'palabra_clave' => 'TINTA', 'orden' => 13],

            // === SOFTWARE / LICENCIAS (orden 14) ===
            ['tipo' => 'software', 'palabra_clave' => 'LICENCIA', 'orden' => 14],
            ['tipo' => 'software', 'palabra_clave' => 'IPERIUS', 'orden' => 14],
            ['tipo' => 'software', 'palabra_clave' => 'NINJA', 'orden' => 14],
            ['tipo' => 'software', 'palabra_clave' => 'LICENCIA VPN', 'orden' => 14],
            ['tipo' => 'software', 'palabra_clave' => 'BDP', 'orden' => 14],

            // === CONSULTORIA (orden 15) ===
            ['tipo' => 'consultoria', 'palabra_clave' => 'ASESORAMIENTO', 'orden' => 15],
            ['tipo' => 'consultoria', 'palabra_clave' => 'CONSULTORIA', 'orden' => 15],
            ['tipo' => 'consultoria', 'palabra_clave' => 'CIBERSEGURIDAD', 'orden' => 15],

            // === DISENO WEB / MARKETING (orden 16) ===
            ['tipo' => 'diseno_web', 'palabra_clave' => 'DISENO WEB', 'orden' => 16],
            ['tipo' => 'diseno_web', 'palabra_clave' => 'SEO', 'orden' => 16],
            ['tipo' => 'diseno_web', 'palabra_clave' => 'AUDIOVISUAL', 'orden' => 16],
            ['tipo' => 'diseno_web', 'palabra_clave' => 'MARKETING', 'orden' => 16],
            ['tipo' => 'diseno_web', 'palabra_clave' => 'PAGINA WEB', 'orden' => 16],
            ['tipo' => 'diseno_web', 'palabra_clave' => 'LANDING PAGE', 'orden' => 16],
            ['tipo' => 'diseno_web', 'palabra_clave' => 'PRESTASHOP', 'orden' => 16],
            ['tipo' => 'diseno_web', 'palabra_clave' => 'TIENDA ONLINE', 'orden' => 16],
            ['tipo' => 'diseno_web', 'palabra_clave' => 'LEY DE COOKIES', 'orden' => 16],
            ['tipo' => 'diseno_web', 'palabra_clave' => 'TRADUCCION WEB', 'orden' => 16],

            // === MONITORES (orden 17) ===
            ['tipo' => 'monitor', 'palabra_clave' => 'MONITOR', 'orden' => 17],
            ['tipo' => 'monitor', 'palabra_clave' => 'SOPORTE BRAZO', 'orden' => 17],

            // === PERIFERICOS (orden 18) ===
            ['tipo' => 'periferico', 'palabra_clave' => 'TECLADO', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'RATON', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'AURICULAR', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'WEBCAM', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'WEB CAM', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'CARGADOR', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'CASCOS', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'MICROFONO', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'ALIMENTADOR', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'AIRPODS', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'LOGITECH', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'HUB USB', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'PENDRIVE', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'PEN DRIVE', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'ALTAVOZ', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'ALTAVOCES', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'BEATS', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'APPLE PENCIL', 'orden' => 18],
            ['tipo' => 'periferico', 'palabra_clave' => 'LECTOR CODIGO', 'orden' => 18],

            // === CABLES / RED (orden 19) ===
            ['tipo' => 'cable_red', 'palabra_clave' => 'CABLE USB', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'CABLE HDMI', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'CABLE ETHERNET', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'CABLE RJ45', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'LATIGUILLO', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'LATIGILLO', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'SWITCH', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'ROUTER', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'ACCESS POINT', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'ANTENA', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'CABLE DE RED', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'CABLE RED', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'BOBINA CABLE', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'CONECTOR RJ45', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'ROSETA', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'ARMARIO RACK', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'PANEL DE PARCHEO', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'PASACABLES', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'UBIQUITI', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'CABLE DISPLAY', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'CABLE SATA', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'CONVERSOR', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'ADAPTADOR', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'ENLACE', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'EMPALME RED', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'FUNDA CONECTOR', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'TRANSCEPTOR', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'MIKROTIK', 'orden' => 19],
            ['tipo' => 'cable_red', 'palabra_clave' => 'POE', 'orden' => 19],

            // === COMPONENTES INTERNOS (orden 20) ===
            ['tipo' => 'componente', 'palabra_clave' => 'MEMORIA RAM', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'DISCO SSD', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'DISCO DURO', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'AMPLIACION DISCO', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'SSD', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'PROCESADOR', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'PLACA BASE', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'FUENTE DE ALIMENTACION', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'FUENTE ALIMENTACION', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'MODULO RAM', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'TARJETA GRAFICA', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'DISIPADOR', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'NVIDIA', 'orden' => 20],
            ['tipo' => 'componente', 'palabra_clave' => 'VENTILADOR CPU', 'orden' => 20],

            // === HARDWARE (orden 21 - mas generico, al final) ===
            ['tipo' => 'hardware', 'palabra_clave' => 'PORTATIL', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'ORDENADOR', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'IPHONE', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'TABLET', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'MINIPC', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'LEOTEC', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'SAMSUNG GALAXY', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'APPLE IPAD', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'SAI ', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'MACBOOK', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'SURFACE', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'XIAOMI', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'OPPO', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'GOOGLE PIXEL', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'NAS ', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'RASPBERRY', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'TORRE PC', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'INTEL NUC', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'MARCA:', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'IPAD', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'APPLE WATCH', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'ULEFONE', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'DELL ', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'LENOVO', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'ASUS NUC', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'MSI CUBE', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'HP ELITEBOOK', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'HP PROBOOK', 'orden' => 21],
            ['tipo' => 'hardware', 'palabra_clave' => 'CHROMEBOOK', 'orden' => 21],
        ];

        // Cargar keywords existentes para no duplicar
        $db = new \FacturaScripts\Core\Base\DataBase();
        $existentes = [];
        $rows = $db->select("SELECT tipo, palabra_clave FROM lineas_clasificacion");
        if ($rows) {
            foreach ($rows as $row) {
                $existentes[$row['tipo'] . '||' . mb_strtoupper($row['palabra_clave'])] = true;
            }
        }

        foreach ($defaults as $data) {
            $clave = $data['tipo'] . '||' . mb_strtoupper($data['palabra_clave']);
            if (isset($existentes[$clave])) {
                continue;
            }
            $item = new static();
            $item->tipo = $data['tipo'];
            $item->palabra_clave = $data['palabra_clave'];
            $item->activo = true;
            $item->orden = $data['orden'] ?? 10;
            $item->creacion = date('Y-m-d H:i:s');
            $item->save();
        }

        // Eliminar keywords del tipo 'material' (ya no existe en v2.0)
        $db->exec("DELETE FROM lineas_clasificacion WHERE tipo = 'material'");
    }
}
