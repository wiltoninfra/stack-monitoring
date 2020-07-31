<?php

namespace Promo\Services\Notification;

use Illuminate\Support\Facades\Log;
use PicPay\Common\Services\NotificationService as CommonNotificationService;

/**
 * Class NotificationService
 * @package App\Services\Notification
 */
class NotificationService
{

    /**
     * @var mixed
     */
    private $log;

    /**
     * NotificationService constructor.
     */
    public function __construct()
    {
        $this->log = Log::channel('log_php_command_stdout');
    }

    /**
     * @param array $template
     */
    public function sendEmailNotification(array $template): void
    {
        try {
            CommonNotificationService::sendEmailNotification(
                $template['template_id'],
                $template['sender'],
                $template['receiver_type'],
                $template['receiver'],
                $template['email'],
                $template['parameters']
            );
        } catch (\Error $error) {
            \Log::error(
                "notification-email-error",
                (array)$error
            );
        }
    }

    /**
     * @param array $template
     */
    public function sendAppNotification(array $template): void
    {
        try {
            CommonNotificationService::sendAppNotification(
                $template['template_id'],
                $template['sender'],
                $template['receiver_type'],
                $template['receiver'],
                $template['parameters'],
                $template['resource'],
                $template['push'],
                $template['campaign_id'],
                $template['schedule'],
                $template['created_at']
            );


        } catch (\Error $error) {
            \Log::error(
                "notification-app-error",
                (array)$error
            );
        }
    }

    /**
     * @param array $template
     */
    public function sendMassAppNotification(array $template): void
    {
        try {
            CommonNotificationService::sendMassAppNotification(
                $template['template_id'],
                $template['sender'],
                $template['receiver_type'],
                $template['receiver'],
                $template['parameters'],
                $template['resource'],
                $template['push'],
                $template['campaign_id'],
                $template['schedule'],
                $template['created_at']
            );

            $this->log->info("notification-promo", [
                'status_job' => 'success',
                'message_job' => "Success to send in_app to consumer",
                'consumer_id' => $template['receiver'],
                'campaign_id' => $template['campaign_id'],
                'notification_type' => 'in_app'
            ]);

        } catch (\Error $error) {
            $this->log->info("notification-promo", [
                'status_job' => 'false',
                'message_job' => "Fail to send in_app to consumer",
                'consumer_id' => $template['receiver'],
                'campaign_id' => $template['campaign_id'],
                'notification_type' => 'in_app',
                'exception_message' => $error->getMessage()
            ]);
        }
    }

    /**
     * @param array $template
     */
    public function sendMassPushNotification(array $template): void
    {
        try {
            CommonNotificationService::sendMassPushNotification(
                $template['template_id'],
                $template['sender'],
                $template['receiver_type'],
                $template['receiver'],
                $template['parameters'],
                $template['resource'],
                $template['campaign_id'],
                $template['schedule'],
                $template['created_at']
            );

            $this->log->info("notification-promo", [
                'status_job' => 'success',
                'message_job' => "Success to send push to consumer",
                'consumer_id' => $template['receiver'],
                'campaign_id' => $template['campaign_id'],
                'notification_type' => 'push'
            ]);
        } catch (\Error $error) {
            $this->log->info("notification-promo", [
                'status_job' => 'false',
                'message_job' => "Fail to send push to consumer",
                'consumer_id' => $template['receiver'],
                'campaign_id' => $template['campaign_id'],
                'notification_type' => 'push',
                'exception_message' => $error->getMessage()
            ]);
        }
    }

    /**
     * @param array $template
     */
    public function sendPushNotification(array $template): void
    {
        try {
            CommonNotificationService::sendPushNotification(
                $template['template_id'],
                $template['sender'],
                $template['receiver_type'],
                $template['receiver'],
                $template['parameters'],
                $template['resource'],
                $template['campaign_id'],
                $template['schedule'],
                $template['created_at']
            );
        } catch (\Error $error) {
            \Log::error("notification-push-error", (array)$error);
        }
    }

    /**
     * @param array $template
     */
    public function sendSmsNotification(array $template): void
    {
        try {
            CommonNotificationService::sendSmsNotification(
                $template['phone'],
                $template['message'],
                $template['prefix'],
                $template['template_id']
            );

            $this->log->info("notification-promo", [
                'status_job' => 'success',
                'message_job' => "Success to send sms to consumer",
                'consumer_id' => $template['receiver'],
                'campaign_id' => $template['campaign_id'],
                'notification_type' => 'sms'
            ]);

        } catch (\Error $error) {

            $this->log->info("notification-promo", [
                'status_job' => 'false',
                'message_job' => "Fail to send sms to consumer",
                'consumer_id' => $template['receiver'],
                'campaign_id' => $template['campaign_id'],
                'notification_type' => 'sms',
                'exception_message' => $error->getMessage()
            ]);
        }
    }
}

