<?php

namespace FacturaScripts\Plugins\AnalisisLineas;

class Init
{
    public function init(): void
    {
        // Se ejecuta cada vez que carga FacturaScripts
    }

    public function update(): void
    {
        // Crear tabla de clasificaciones manuales si no existe
        $db = new \FacturaScripts\Core\Base\DataBase();
        if (!$db->tableExists('lineas_clasificacion_manual')) {
            $db->exec("CREATE TABLE IF NOT EXISTS lineas_clasificacion_manual ("
                . "id SERIAL,"
                . "idlinea INTEGER NOT NULL,"
                . "tipo VARCHAR(50) NOT NULL,"
                . "fecha TIMESTAMP,"
                . "PRIMARY KEY (id),"
                . "UNIQUE (idlinea)"
                . ")");
        }
    }

    public function uninstall(): void
    {
        // Se ejecuta cuando se desinstala el plugin
    }
}
