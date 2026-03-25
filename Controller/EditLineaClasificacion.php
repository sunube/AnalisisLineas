<?php

namespace FacturaScripts\Plugins\AnalisisLineas\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditLineaClasificacion extends EditController
{
    public function getModelClassName(): string
    {
        return 'LineaClasificacion';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['title'] = 'Palabra Clave';
        $data['menu'] = 'reports';
        $data['icon'] = 'fas fa-tag';
        $data['showonmenu'] = false;
        return $data;
    }
}
