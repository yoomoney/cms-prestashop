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
        Configuration::UpdateValue('YA_METRICS_SET_HASH', Tools::getValue('YA_METRICS_SET_HASH'));
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

    /**
     * @return array
     */
    public function getOptionValues()
    {
        $optionKeys   = array(
            'YA_METRICS_SET_WEBVIZOR',
            'YA_METRICS_SET_CLICKMAP',
            'YA_METRICS_SET_HASH',
            'YA_METRICS_ACTIVE',
            'YA_METRICS_NUMBER',
            'YA_METRICS_ID_APPLICATION',
            'YA_METRICS_PASSWORD_APPLICATION',
            'YA_METRICS_TOKEN',
            'YA_METRICS_CODE',
        );
        $optionValues = array();
        foreach ($optionKeys as $optionKey) {
            $optionValues[$optionKey] = Configuration::get($optionKey);
        }

        return $optionValues;
    }

    /**
     * @param array $prevOptions
     * @return bool
     */
    public function isNeedUpdateToken($prevOptions)
    {
        $tokenOptions = array('YA_METRICS_NUMBER', 'YA_METRICS_ID_APPLICATION', 'YA_METRICS_PASSWORD_APPLICATION');
        foreach ($tokenOptions as $option) {
            if (!Configuration::get($option)) {
                return false;
            }
        }
        foreach ($tokenOptions as $option) {
            if (Configuration::get($option) !== $prevOptions[$option]) {
                return true;
            }
        }

        if (!Configuration::get('YA_METRICS_TOKEN')) {
            return true;
        }

        return false;
    }

    /**
     * @param $state
     * @return void
     */
    public function redirectToOAuth($state)
    {
        Tools::redirect(
            'https://oauth.yandex.ru/authorize?response_type=code&state='.$state
            .'&client_id='
            .Configuration::get('YA_METRICS_ID_APPLICATION')
            .'&client_secret='.Configuration::get('YA_METRICS_PASSWORD_APPLICATION')
        );
    }

    public function sendData()
    {
        $m        = new \YandexMoneyModule\Metrics();
        $response = $m->run();

        $data = array(
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
                $otvet = $m->editCounter();
                $counter = $m->getCounter();
                if (!empty($counter->counter->code)) {
                    Configuration::UpdateValue('YA_METRICS_CODE', $counter->counter->code, true);
                }
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

                        $types = array('YA_METRICS_WISHLIST');
                        foreach ($types as $type) {
                            if (!isset($goals[$type])) {
                                $ret['add_'.$type] = $m->addCounterGoal(array('goal' => $data[$type]));
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
