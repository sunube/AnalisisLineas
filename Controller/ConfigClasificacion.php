<?php

namespace FacturaScripts\Plugins\AnalisisLineas\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

class ConfigClasificacion extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['title'] = 'Configurar Clasificación';
        $data['menu'] = 'informes';
        $data['submenu'] = 'Análisis Líneas';
        $data['icon'] = 'fas fa-cogs';
        $data['showonmenu'] = false;
        return $data;
    }

    protected function createViews(): void
    {
        $this->createViewManoObra();
        $this->createViewMaterial();
    }

    private function createViewManoObra(): void
    {
        $this->addView('ListLineaClasificacion-mo', 'LineaClasificacion', 'Mano de Obra', 'fas fa-hard-hat');
        $this->addSearchFields('ListLineaClasificacion-mo', ['palabra_clave']);
        $this->addFilterSelect('ListLineaClasificacion-mo', 'activo', 'activo', 'activo');

        // Solo mostrar las de tipo mano_de_obra
        $this->setSettings('ListLineaClasificacion-mo', 'btnNew', true);
        $this->setSettings('ListLineaClasificacion-mo', 'btnDelete', true);
    }

    private function createViewMaterial(): void
    {
        $this->addView('ListLineaClasificacion-mat', 'LineaClasificacion', 'Material', 'fas fa-box');
        $this->addSearchFields('ListLineaClasificacion-mat', ['palabra_clave']);
        $this->addFilterSelect('ListLineaClasificacion-mat', 'activo', 'activo', 'activo');

        $this->setSettings('ListLineaClasificacion-mat', 'btnNew', true);
        $this->setSettings('ListLineaClasificacion-mat', 'btnDelete', true);
    }

    protected function loadData($viewName, $view): void
    {
        switch ($viewName) {
            case 'ListLineaClasificacion-mo':
                $where = [
                    new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('tipo', 'mano_de_obra'),
                ];
                $view->loadData('', $where);
                break;

            case 'ListLineaClasificacion-mat':
                $where = [
                    new \FacturaScripts\Core\Base\DataBase\DataBaseWhere('tipo', 'material'),
                ];
                $view->loadData('', $where);
                break;
        }
    }
}
