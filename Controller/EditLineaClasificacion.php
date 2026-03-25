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
        $data['name'] = 'EditLineaClasificacion';
        $data['title'] = 'palabra-clave';
        $data['menu'] = 'reports';
        $data['icon'] = 'fa-solid fa-tag';
        $data['showonmenu'] = false;
        return $data;
    }
}
