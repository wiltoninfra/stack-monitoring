<?php
namespace Promo\Services\DigitalAccount;
use Illuminate\Support\Facades\Log;
use PicPay\Common\Services\DigitalAccount\DigitalAccountService as CommonDigitalAccountService;
use PicPay\Common\Services\DigitalAccount\Enums\TransactionTypeEnum;


class DigitalAccountService
{

    /**
     * @param int $consumer_id
     * @param float $reward_value
     * @return bool
     */
    public function isAccountUnderLimits(int $consumer_id, float $reward_value): bool
    {
        Log::info('requesting_digital_account',
            [
                'context' => 'cashback',
                'status' => 'success',
                'consumer_id' => $consumer_id,
                'reward_value' => $reward_value
            ]
        );

        try{
            $digitalAccountService = new CommonDigitalAccountService();
            $limit_info = $digitalAccountService->getAvailableLimit(
                $consumer_id,
                $reward_value,
                TransactionTypeEnum::REWARD
            );

            Log::info('response_digital_account',
                [
                    'context' => 'cashback',
                    'status' => 'success',
                    'under_limit' => $limit_info['under_limit']
                ]
            );
            return $limit_info["under_limit"];
        }catch (\Exception $exception){
            Log::error("requesting_digital_account", [
                'context' => 'cashback',
                'status' => 'fail',
                'exception_message' => $exception->getMessage()
            ]);
            return true;
        }

    }

}
