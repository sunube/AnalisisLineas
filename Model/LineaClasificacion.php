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

    /** @var string mano_de_obra|material */
    public $tipo;

    /** @var string */
    public $palabra_clave;

    /** @var bool */
    public $activo;

    /** @var string */
    public $creacion;

    public function clear(): void
    {
        parent::clear();
        $this->tipo = 'mano_de_obra';
        $this->activo = true;
        $this->creacion = date('Y-m-d H:i:s');
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

    /**
     * Devuelve todas las palabras clave activas de un tipo
     */
    public static function getByTipo(string $tipo): array
    {
        $model = new static();
        $where = [
            new DataBaseWhere('tipo', $tipo),
            new DataBaseWhere('activo', true),
        ];
        return $model->all($where, [], 0, 0);
    }

    /**
     * Devuelve todas las palabras clave activas
     */
    public static function getAllActive(): array
    {
        $model = new static();
        $where = [
            new DataBaseWhere('activo', true),
        ];
        return $model->all($where, [], 0, 0);
    }

    /**
     * Inserta las palabras clave por defecto si la tabla esta vacia
     */
    public static function seedDefaults(): void
    {
        $model = new static();
        if ($model->count() > 0) {
            return;
        }

        $defaults = [
            // Mano de obra - palabras clave
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'INSTALACION'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'CONFIGURACION'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PUESTA EN MARCHA'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'MANO DE OBRA'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'MONTAJE'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PROGRAMACION'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'DESPLAZAMIENTO'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'SERVICIO TECNICO'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'FORMACION'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'MANTENIMIENTO'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'REPARACION'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'ASISTENCIA'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'SOPORTE'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'FORMATEO'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'RECUPERACION'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PREPARAR'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'PROBLEMA CON'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'CALIBRACION'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'DIAGNOSTICO'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'REVISION'],
            ['tipo' => 'mano_de_obra', 'palabra_clave' => 'INTERVENCION'],
        ];

        foreach ($defaults as $data) {
            $item = new static();
            $item->tipo = $data['tipo'];
            $item->palabra_clave = $data['palabra_clave'];
            $item->activo = true;
            $item->creacion = date('Y-m-d H:i:s');
            $item->save();
        }
    }
}
