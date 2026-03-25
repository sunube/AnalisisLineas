<?php

namespace FacturaScripts\Plugins\AnalisisLineas\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;

class LineaClasificacionManual extends ModelClass
{
    use ModelTrait;

    /** @var int */
    public $id;

    /** @var int */
    public $idlinea;

    /** @var string */
    public $tipo;

    /** @var string */
    public $fecha;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = date('Y-m-d H:i:s');
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'lineas_clasificacion_manual';
    }

    /**
     * Devuelve todas las clasificaciones manuales indexadas por idlinea
     */
    public static function getAllIndexed(): array
    {
        $db = new \FacturaScripts\Core\Base\DataBase();

        // Verificar que la tabla existe antes de consultar
        if (!$db->tableExists('lineas_clasificacion_manual')) {
            return [];
        }

        $rows = $db->select("SELECT idlinea, tipo FROM lineas_clasificacion_manual");
        $result = [];
        if ($rows) {
            foreach ($rows as $row) {
                $result[(int) $row['idlinea']] = $row['tipo'];
            }
        }
        return $result;
    }
}
