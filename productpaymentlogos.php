<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 */


if (!defined('_TB_VERSION_')) {
    exit;
}

class ProductPaymentLogos extends Module
{
    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'productpaymentlogos';
        $this->tab = 'front_office_features';
        $this->version = '2.1.1';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Block Product Payment Logos');
        $this->description = $this->l('Displays the logos of the available payment systems on the product page.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function install()
    {
        Configuration::updateValue('PRODUCTPAYMENTLOGOS_IMG', 'payment-logo.png');
        Configuration::updateValue('PRODUCTPAYMENTLOGOS_LINK', '');
        Configuration::updateValue('PRODUCTPAYMENTLOGOS_TITLE', '');

        $this->_clearCache('productpaymentlogos.tpl');

        return parent::install() && $this->registerHook('displayProductButtons') && $this->registerHook('header');
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        Configuration::deleteByName('PRODUCTPAYMENTLOGOS_IMG');
        Configuration::deleteByName('PRODUCTPAYMENTLOGOS_LINK');
        Configuration::deleteByName('PRODUCTPAYMENTLOGOS_TITLE');

        return parent::uninstall();
    }

    /**
     * @return string|null
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayProductButtons($params)
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return null;
        }

        if (!$this->isCached('productpaymentlogos.tpl', $this->getCacheId())) {
            $this->smarty->assign([
                'banner_img' => 'img/' . Configuration::get('PRODUCTPAYMENTLOGOS_IMG'),
                'banner_link' => Configuration::get('PRODUCTPAYMENTLOGOS_LINK'),
                'banner_title' => Configuration::get('PRODUCTPAYMENTLOGOS_TITLE')
            ]);
        }

        return $this->display(__FILE__, 'productpaymentlogos.tpl', $this->getCacheId());
    }

    /**
     * @throws PrestaShopException
     */
    public function hookHeader($params)
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return;
        }

        $this->context->controller->addCSS($this->_path . 'productpaymentlogos.css', 'all');
    }

    /**
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function getContent()
    {
        return $this->postProcess() . $this->renderForm();
    }

    /**
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitStoreConf')) {
            Configuration::updateValue('PRODUCTPAYMENTLOGOS_LINK', Tools::getValue('PRODUCTPAYMENTLOGOS_LINK'));
            Configuration::updateValue('PRODUCTPAYMENTLOGOS_TITLE', Tools::getValue('PRODUCTPAYMENTLOGOS_TITLE'));

            $uploadedFile = $_FILES['PRODUCTPAYMENTLOGOS_IMG'] ?? null;

            if ($uploadedFile && !empty($uploadedFile['tmp_name'])) {
                $fileInfo = pathinfo($uploadedFile['name']);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array(strtolower($fileInfo['extension']), $allowedExtensions)) {
                    return $this->displayError($this->l('Invalid image format. Supported formats: JPG, JPEG, PNG, GIF, WebP.'));
                }

                $fileName = md5($uploadedFile['name']) . '.' . $fileInfo['extension'];
                $filePath = dirname(__FILE__) . '/img/' . $fileName;

                if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
                    return $this->displayError($this->l('An error occurred while attempting to upload the file.'));
                }

                // Remove old image if exists
                $oldFileName = Configuration::get('PRODUCTPAYMENTLOGOS_IMG');
                $oldFilePath = dirname(__FILE__) . '/img/' . $oldFileName;
                if ($oldFileName && file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }

                Configuration::updateValue('PRODUCTPAYMENTLOGOS_IMG', $fileName);
                $this->_clearCache('productpaymentlogos.tpl');
            }

            Tools::redirectAdmin('index.php?tab=AdminModules&conf=6&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'));
        }

        return '';
    }

    /**
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Block heading'),
                        'name' => 'PRODUCTPAYMENTLOGOS_TITLE',
                        'desc' => $this->l('You can choose to add a heading above the logos.')
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->l('Block image'),
                        'name' => 'PRODUCTPAYMENTLOGOS_IMG',
                        'thumb' => '../modules/' . $this->name . '/img/' . Configuration::get('PRODUCTPAYMENTLOGOS_IMG'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Image link'),
                        'name' => 'PRODUCTPAYMENTLOGOS_LINK',
                        'desc' => $this->l('You can either upload your own image using the form above, or link to it from the "Image link" option.')
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ]
            ],
        ];

        /** @var AdminController $controller */
        $controller = $this->context->controller;
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStoreConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * @throws PrestaShopException
     */
    public function getConfigFieldsValues()
    {
        return [
            'PRODUCTPAYMENTLOGOS_IMG' => Tools::getValue('PRODUCTPAYMENTLOGOS_IMG', Configuration::get('PRODUCTPAYMENTLOGOS_IMG')),
            'PRODUCTPAYMENTLOGOS_LINK' => Tools::getValue('PRODUCTPAYMENTLOGOS_LINK', Configuration::get('PRODUCTPAYMENTLOGOS_LINK')),
            'PRODUCTPAYMENTLOGOS_TITLE' => Tools::getValue('PRODUCTPAYMENTLOGOS_TITLE', Configuration::get('PRODUCTPAYMENTLOGOS_TITLE')),
        ];
    }
}
