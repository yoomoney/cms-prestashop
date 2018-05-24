<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

namespace YandexMoneyModule\Models;

use Configuration;
use Tools;

class MetricsModel extends AbstractModel
{
    private $valid;

    public function validateOptions()
    {
        $this->valid = false;

        $errors = '';
        Configuration::UpdateValue('YA_METRICS_SET_WEBVIZOR', Tools::getValue('YA_METRICS_SET_WEBVIZOR'));
        Configuration::UpdateValue('YA_METRICS_SET_CLICKMAP', Tools::getValue('YA_METRICS_SET_CLICKMAP'));
        Configuration::UpdateValue('YA_METRICS_SET_OUTLINK', Tools::getValue('YA_METRICS_SET_OUTLINK'));
        Configuration::UpdateValue('YA_METRICS_SET_OTKAZI', Tools::getValue('YA_METRICS_SET_OTKAZI'));
        Configuration::UpdateValue('YA_METRICS_SET_HASH', Tools::getValue('YA_METRICS_SET_HASH'));
        Configuration::UpdateValue('YA_METRICS_CELI_CART', Tools::getValue('YA_METRICS_CELI_CART'));
        Configuration::UpdateValue('YA_METRICS_CELI_ORDER', Tools::getValue('YA_METRICS_CELI_ORDER'));
        Configuration::UpdateValue('YA_METRICS_CELI_WISHLIST', Tools::getValue('YA_METRICS_CELI_WISHLIST'));
        Configuration::UpdateValue('YA_METRICS_ACTIVE', Tools::getValue('YA_METRICS_ACTIVE'));

        if (Tools::getValue('YA_METRICS_ID_APPLICATION') == '') {
            $errors .= $this->module->displayError($this->module->l('Not filled in the application ID!'));
        } else {
            Configuration::UpdateValue('YA_METRICS_ID_APPLICATION', Tools::getValue('YA_METRICS_ID_APPLICATION'));
        }

        if (Tools::getValue('YA_METRICS_PASSWORD_APPLICATION') == '') {
            $errors .= $this->module->displayError(
                $this->module->l('Not filled with an application-specific Password!')
            );
        } else {
            Configuration::UpdateValue(
                'YA_METRICS_PASSWORD_APPLICATION',
                Tools::getValue('YA_METRICS_PASSWORD_APPLICATION')
            );
        }

        if (Tools::getValue('YA_METRICS_NUMBER') == '') {
            $errors .= $this->module->displayError($this->module->l('Not filled the room counter Medici!'));
        } else {
            Configuration::UpdateValue('YA_METRICS_NUMBER', Tools::getValue('YA_METRICS_NUMBER'));
        }

        if ($errors == '') {
            $errors      = $this->module->displayConfirmation($this->module->l('Settings saved successfully!'));
            $this->valid = true;
        }

        return $errors;
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function initConfiguration()
    {
    }

    public function sendData()
    {
        $m        = new \YandexMoneyModule\Metrics();
        $response = $m->run();

        $data = array(
            'YA_METRICS_CART'     => array(
                'name'       => 'YA_METRICS_CART',
                'flag'       => 'basket',
                'type'       => 'action',
                'class'      => 1,
                'depth'      => 0,
                'conditions' => array(
                    array(
                        'url'  => 'metrikaCart',
                        'type' => 'exact',
                    ),
                ),

            ),
            'YA_METRICS_ORDER'    => array(
                'name'       => 'YA_METRICS_ORDER',
                'flag'       => 'order',
                'type'       => 'action',
                'class'      => 1,
                'depth'      => 0,
                'conditions' => array(
                    array(
                        'url'  => 'metrikaOrder',
                        'type' => 'exact',
                    ),
                ),

            ),
            'YA_METRICS_WISHLIST' => array(
                'name'       => 'YA_METRICS_WISHLIST',
                'flag'       => '',
                'type'       => 'action',
                'class'      => 1,
                'depth'      => 0,
                'conditions' => array(
                    array(
                        'url'  => 'metrikaWishlist',
                        'type' => 'exact',
                    ),
                ),

            ),
        );

        $ret   = array();
        $error = '';
        if (Configuration::get('YA_METRICS_TOKEN') != '') {
            if ($response) {
                $counter = $m->getCounter();
                if (!empty($counter->counter->code)) {
                    Configuration::UpdateValue('YA_METRICS_CODE', $counter->counter->code, true);
                }
                $otvet = $m->editCounter();
                if (!is_null($otvet)) {
                    if ($otvet->counter->id != Configuration::get('YA_METRICS_NUMBER')) {
                        $error .= $this->module->displayError(
                            $this->module->l(
                                'Saving the settings the meter is not the meter number is incorrect.'
                            )
                        );
                    } else {
                        $tmp_goals = $m->getCounterGoals();
                        $goals     = array();
                        foreach ($tmp_goals->goals as $goal) {
                            $goals[$goal->name] = $goal;
                        }

                        $types = array('YA_METRICS_ORDER', 'YA_METRICS_WISHLIST', 'YA_METRICS_CART');
                        foreach ($types as $type) {
                            $conf = explode('_', $type);
                            $conf = $conf[0].'_'.$conf[1].'_CELI_'.$conf[2];
                            if (Configuration::get($conf) == 0 && isset($goals[$type])) {
                                $ret['delete_'.$type] = $m->deleteCounterGoal($goals[$type]->id);
                            } elseif (Configuration::get($conf) == 1 && !isset($goals[$type])) {
                                $params            = $data[$type];
                                $ret['add_'.$type] = $m->addCounterGoal(array('goal' => $params));
                            }
                        }
                    }
                } else {
                    $error .= $this->module->displayError($m->errors);
                }
            } elseif (!empty($m->errors)) {
                $error .= $this->module->displayError($m->errors);
            }
        } else {
            $error .= $this->module->displayError(
                $this->module->l(
                    'The token for authorization is missing! Get the token and repeat!'
                )
            );
        }

        if ($error == '') {
            return $this->module->displayConfirmation(
                $this->module->l(
                    'Data was successfully sent and saved! Code metrici updated pages automatically.'
                )
            );
        } else {
            return $error;
        }
    }
}
