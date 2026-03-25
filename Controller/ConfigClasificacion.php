<?php

namespace FacturaScripts\Plugins\AnalisisLineas\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

class ConfigClasificacion extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['name'] = 'ConfigClasificacion';
        $data['title'] = 'config-clasificacion';
        $data['menu'] = 'reports';
        $data['icon'] = 'fa-solid fa-cogs';
        $data['showonmenu'] = false;
        return $data;
    }

    protected function createViews(): void
    {
        $this->addView('ListLineaClasificacion', 'LineaClasificacion', 'palabras-clave', 'fa-solid fa-tags');
        $this->addSearchFields('ListLineaClasificacion', ['palabra_clave']);
        $this->addFilterSelect('ListLineaClasificacion', 'tipo', 'tipo', 'tipo');
        $this->addOrderBy('ListLineaClasificacion', ['tipo', 'palabra_clave'], 'tipo');
        $this->addOrderBy('ListLineaClasificacion', ['palabra_clave'], 'palabra_clave');
    }

    protected function loadData($viewName, $view): void
    {
        switch ($viewName) {
            case 'ListLineaClasificacion':
                $view->loadData();
                break;
        }
    }
}
