<?php

namespace Webident\OneSignal;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Container\Container;
use Webident\OneSignal\Exceptions\FailedToSendNotificationException;
use Illuminate\Support\Facades\Log;


class OneSignal {

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client, Container $app)
    {
        $config = app('config');

        $this->client = $client;

        $this->appId = $config->get('oneSignal.appId');

        $this->API_KEY = 'Basic ' . $config->get('oneSignal.appKey');

        $this->Authorization = 'Basic ' . $config->get('oneSignal.user_auth_key');

        $this->Url = $config->get('oneSignal.url');
    }


    /**
     * https://documentation.onesignal.com/reference#create-notification
     * @param  $title - Required
     * @param  $massage - Required
     * @param  $data - Optional | array
     * @param  $url - Optional
     * @param  $buttons - Optional
     *
     * @return mixed
     * @throws FailedToSendNotificationException
     */
    public function SendNotificationToAll($title, $massage, $data = [], $url = null, $buttons = null)
    {

        $params = [
            'app_id'            => $this->appId,
            'included_segments' => ['All'],
            'headings'          => [
                'en' => $title,
            ],
            'contents'          => [
                'en' => $massage,
            ],
        ];

        if (isset($data))
        {
            $params['data'] = $data;
        }

        if (isset($url))
        {
            $params['url'] = $url;
        }

        if (isset($button))
        {
            $params['buttons'] = $buttons;
        }

        return $this->post($params, 'notifications', $this->Authorization);
    }

    /**
     * https://documentation.onesignal.com/reference#create-notification
     * @param       $headings - Required
     * @param       $contents - Required
     * @param array $OneSignalIds - Required | array
     * @param array $data - Optional | array
     * @param       $url - Optional
     * @param       $buttons - Optional
     *
     * @return mixed
     * @throws FailedToSendNotificationException
     */
    public function SendNotificationToSpecificUsers(
        $headings,
        $contents,
        $OneSignalIds = [],
        $data = [],
        $url = null,
        $buttons = null
    )
    {

        $params = [
            'app_id'             => $this->appId,
            'include_player_ids' => $OneSignalIds,
            'headings'           => $headings,
            'contents'           => $contents,
            /*
                "alert": {
    "title": "Beautiful View",
    "subtitle": "",
    "body" : "Denali, Alaska",
},
"mutable-content": 1
                */

        ];

        if (isset($data))
        {
            $params['data'] = $data;
        }

        if (isset($url))
        {
            $params['url'] = $url;
        }

        if (isset($button))
        {
            $params['buttons'] = $buttons;
        }

        return $this->post($params, 'notifications', $this->Authorization);
    }

    public function SendNotificationToSpecificUsersAdy(
        $headings,
        $contents,
        $OneSignalIds = [],
        $data = [],
        $url = null,
        $buttons = null,
        $tenantIds=[],
        $subsIds=[]
    )
    {
        \Illuminate\Support\Facades\Log::info('OneSignal Ids to send to: ' . json_encode($tenantIds));

        $params = [
            'app_id' => $this->appId,
            //'include_player_ids' => $OneSignalIds,
            //'include_subscription_ids' => count($subsIds) > 0 ? $subsIds : [],
            'include_external_user_ids' => count($tenantIds) > 0 ? $tenantIds : [],
            'large_icon' => 'ic_stat_onesignal_default',
            'include_aliases' => [
                'external_id' => count($tenantIds) > 0 ? $tenantIds : [],
                // 'onesignal_id' => $OneSignalIds,
            ],
            "channel_for_external_user_ids" => "push",
            "target_channel" => "push",
            'headings' => $headings,
            'contents' => $contents,
            // "alert": {
            //     "title": "Beautiful View",
            //     "subtitle": "",
            //     "body" : "Denali, Alaska",
            // },
            // "mutable-content": 1
            "alert" => [
                "title" => $headings,
                "subtitle" => "",
                "body" => $contents,
            ],
            "mutable_content" => 1,
        ];

        if (isset($data))
        {
            $params['data'] = $data;
        }

        if (isset($url))
        {
            $params['url'] = $url;
        }

        if (isset($button))
        {
            $params['buttons'] = $buttons;
        }

        if (app()->environment('local') && false)
        {
            Log::debug('local enviroment, dont send messages', [
                'params' => $params,
                'onesignalIds' => $OneSignalIds,
                'tenantIds' => $tenantIds,
                'subsIds' => $subsIds
            ]);

            return;
        }

        Log::debug('OneSignal response params: ' , [json_encode($params)]);
        return $this->post($params, 'notifications', $this->Authorization);
    }


    /**
     * https://documentation.onesignal.com/reference#cancel-notification
     * @param $Notification_id
     *
     * @return string
     */
    public function CancelNotification($Notification_id)
    {
        return $this->delete($Notification_id, 'notifications');

    }


    /**
     * https://documentation.onesignal.com/reference#view-apps-apps
     * @return string
     */
    public function ViewApps()
    {
        return $this->get('apps', $this->Authorization);

    }

    /**
     * https://documentation.onesignal.com/reference#view-an-app
     *
     * @param null $appId
     *
     * @return string
     */
    public function ViewApp($appId = null)
    {
        $appId = $appId == null ? $this->appId : $appId;

        return $this->get('apps/' . $appId, $this->Authorization);
    }


    /**
     * https://documentation.onesignal.com/reference#create-an-app
     * @param       $app_name
     * @param array $params
     *
     * @return string
     */
    public function CreateApp($app_name, $params = [])
    {
        $data = [
            'name' => $app_name,
        ];

        $data = array_merge($data, $params);

        return $this->post($data, 'apps', $this->Authorization);
    }

    /**
     * https://documentation.onesignal.com/reference#update-an-app
     * @param $app_id
     * @param $params
     *
     * @return mixed
     * @throws \Moathdev\OneSignal\Exceptions\FailedToSendNotificationException
     */
    public function UpdateApp($app_id, $params)
    {
        return $this->put($params, 'apps/' . $app_id, $this->Authorization);
    }

    /**
     * https://documentation.onesignal.com/reference#view-devices
     * Note : Unavailable for Apps > 100,000 users .
     *
     * @param null $limit | How many devices to return. Max is 300. Default is 300 .
     * @param null $offset | Result offset. Default is 0. Results are sorted by id .
     *
     * @return mixed
     * @throws \Moathdev\OneSignal\Exceptions\FailedToSendNotificationException
     */
    public function ViewDevices($limit = null, $offset = null)
    {
        return $this->get('players?app_id=' . $this->appId . '&limit=' . $limit . '&offset=' . $offset, $this->Authorization);
    }


    /**
     * https://documentation.onesignal.com/reference#add-a-device
     * @param       $device_type
     * @param array $params
     *
     * @return string
     * @internal param $app_name
     */
    public function AddDevice($device_type, $params = [])
    {
        $data = [
            'device_type' => $device_type,
        ];

        $data = array_merge($data, $params);

        return $this->post($data, 'players', $this->Authorization);
    }

    /**
     * https://documentation.onesignal.com/reference#update-an-app
     * @param string $device_id The device's OneSignal ID
     * @param        $params
     *
     * @return mixed
     * @throws \Moathdev\OneSignal\Exceptions\FailedToSendNotificationException
     */
    public function UpdateDevice($device_id, $params)
    {
        return $this->put($params, 'players/' . $device_id, $this->Authorization);
    }


    public function TrackOpen($notificationId, $opened = true)
    {
        return $this->put($opened, 'notifications/' . $notificationId, $this->appId);
    }


    /**
     * @param $params
     * @param $action
     * @param $auth
     *
     * @return string
     * @throws FailedToSendNotificationException
     */
    public function post($params, $action, $auth)
    {
        try
        {
            $response = $this->client->post($this->Url . $action, [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => $auth,
                ],
                'body'    => json_encode($params),
            ]);
            Log::debug('Onesignal response: ' , [json_encode($response->getStatusCode())]);
            $rbody = $response->getBody()->getContents();
            if(is_string($rbody)){
                $bodyD = json_decode($rbody, true);
            } else {
                $bodyD = $rbody;
            }

            return [
                'body'   => $bodyD,
                'status_code' => $response->getStatusCode(),
            ];
        }
        catch (ClientException $e) {
            throw new FailedToSendNotificationException('Failed to send notification .', 0, $e);
        }
    }

    /**
     * @param $Notification_id
     * @param $action
     *
     * @return string
     * @throws FailedToSendNotificationException
     */
    public function delete($Notification_id, $action)
    {
        try {
            return json_decode($this->client->delete($this->Url . $action . '/' . $Notification_id . '?app_id=' . $this->appId,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => $this->API_KEY,
                    ],
                ])->getBody()->getContents());
        }
        catch (ClientException $e) {
            throw new FailedToSendNotificationException('Failed to delete notification: ' . $Notification_id . ' .', 0, $e);
        }
    }

    /**
     * @param $action
     * @param $auth
     *
     * @return string
     * @throws FailedToSendNotificationException
     */
    public function get($action, $auth)
    {
        try
        {
            return json_decode($this->client->get($this->Url . $action, [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => $auth,
                ],
            ])->getBody()->getContents());
        }
        catch ( ClientException $e )
        {
            throw new FailedToSendNotificationException('Failed to get' . $action, 0, $e);
        }
    }

    public function put($params, $action, $auth)
    {
        try
        {
            return json_decode($this->client->put($this->Url . $action, [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => $auth,
                ],
                'body'    => json_encode($params),
            ])->getBody()->getContents());
        }
        catch ( ClientException $e )
        {
            throw new FailedToSendNotificationException('Failed to send notification .', 0, $e);
        }
    }
} // End Class
