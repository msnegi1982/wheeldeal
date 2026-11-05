<?php

namespace App\Services\Otp;

use App\Enums\OtpChannel;
use App\Exceptions\OtpDeliveryException;
use App\Services\Otp\Contracts\OtpDriverInterface;

class OtpManager
{
    /**
     * @var array<string, OtpDriverInterface>
     */
    private array $resolved = [];

    public function forChannel(OtpChannel $channel): OtpDriverInterface
    {
        $name = $channel === OtpChannel::WHATSAPP
            ? (string) config('otp.whatsapp_driver', 'log')
            : (string) config('otp.sms_driver', 'log');

        return $this->driver($name);
    }

    public function driver(string $name): OtpDriverInterface
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $config = config('otp.drivers.'.$name);

        if (! is_array($config) || empty($config['class']) || ! class_exists($config['class'])) {
            throw new OtpDeliveryException('Unknown OTP driver: '.$name);
        }

        $driver = app($config['class'], ['config' => $config]);

        if (! $driver instanceof OtpDriverInterface) {
            throw new OtpDeliveryException('OTP driver '.$name.' does not implement the driver contract.');
        }

        return $this->resolved[$name] = $driver;
    }
}
