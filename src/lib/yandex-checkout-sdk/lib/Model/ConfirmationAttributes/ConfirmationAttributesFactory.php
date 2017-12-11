<?php

namespace YaMoney\Model\ConfirmationAttributes;

use YaMoney\Model\ConfirmationType;

/**
 * Class ConfirmationAttributesFactory
 *
 * @package YaMoney\Model\ConfirmationAttributes
 */
class ConfirmationAttributesFactory
{
    private $typeClassMap = array(
        ConfirmationType::CODE_VERIFICATION => 'ConfirmationAttributesCodeVerification',
        ConfirmationType::DEEPLINK          => 'ConfirmationAttributesDeepLink',
        ConfirmationType::EXTERNAL          => 'ConfirmationAttributesExternal',
        ConfirmationType::REDIRECT          => 'ConfirmationAttributesRedirect',
    );

    /**
     * @param string $type
     * @return AbstractConfirmationAttributes
     */
    public function factory($type)
    {
        if (!is_string($type)) {
            throw new \InvalidArgumentException('Invalid confirmation attributes value in confirmation factory');
        }
        if (!array_key_exists($type, $this->typeClassMap)) {
            throw new \InvalidArgumentException('Invalid confirmation attributes value type "'.$type.'"');
        }
        $className = __NAMESPACE__ . '\\' . $this->typeClassMap[$type];
        return new $className();
    }

    /**
     * @param array $data
     * @param string|null $type
     * @return AbstractConfirmationAttributes
     */
    public function factoryFromArray(array $data, $type = null)
    {
        if ($type === null) {
            if (array_key_exists('type', $data)) {
                $type = $data['type'];
                unset($data['type']);
            } else {
                throw new \InvalidArgumentException(
                    'Parameter type not specified in ConfirmationAttributesFactory.factoryFromArray()'
                );
            }
        }
        $confirmation = $this->factory($type);
        foreach ($data as $key => $value) {
            if ($confirmation->offsetExists($key)) {
                $confirmation->offsetSet($key, $value);
            }
        }
        return $confirmation;
    }
}