<?php

namespace YaMoney\Model\Confirmation;

use YaMoney\Model\ConfirmationType;

/**
 * Class ConfirmationFactory
 *
 * @package YaMoney\Model\Confirmation
 */
class ConfirmationFactory
{
    private $typeClassMap = array(
        ConfirmationType::CODE_VERIFICATION => 'ConfirmationCodeVerification',
        ConfirmationType::DEEPLINK          => 'ConfirmationDeepLink',
        ConfirmationType::EXTERNAL          => 'ConfirmationExternal',
        ConfirmationType::REDIRECT          => 'ConfirmationRedirect',
    );

    /**
     * @param string $type
     * @return AbstractConfirmation
     */
    public function factory($type)
    {
        if (!is_string($type)) {
            throw new \InvalidArgumentException('Invalid confirmation value in confirmation factory');
        }
        if (!array_key_exists($type, $this->typeClassMap)) {
            throw new \InvalidArgumentException('Invalid confirmation value type "'.$type.'"');
        }
        $className = __NAMESPACE__ . '\\' . $this->typeClassMap[$type];
        return new $className();
    }

    /**
     * @param array $data
     * @param string|null $type
     * @return AbstractConfirmation
     */
    public function factoryFromArray(array $data, $type = null)
    {
        if ($type === null) {
            if (array_key_exists('type', $data)) {
                $type = $data['type'];
                unset($data['type']);
            } else {
                throw new \InvalidArgumentException(
                    'Parameter type not specified in ConfirmationFactory.factoryFromArray()'
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