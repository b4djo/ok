<?php

namespace badjo\ok\app\lib;

/**
 * Class Ok
 * @package badjo\ok\app\lib
 */
class Ok
{
    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $privateKey;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $apiUrl = 'https://api.ok.ru/fb.do';

    /**
     * Ok constructor.
     * @param string $accessToken
     * @param string $privateKey
     * @param string $publicKey
     */
    public function __construct(string $accessToken, string $privateKey, string $publicKey)
    {
        $this->accessToken = $accessToken;
        $this->privateKey  = $privateKey;
        $this->publicKey   = $publicKey;
    }

    /**
     * @see https://apiok.ru/dev/methods/rest/mediatopic/mediatopic.post
     * @param int $groupId
     * @param array $data
     * @param array $attachments
     */
    public function postGroupWall(int $groupId, array $data, array $attachments)
    {
        $params = [
            'application_key' =>$this->publicKey,
            'method'          => 'mediatopic.post',
            'gid'             => $groupId,
            'type'            => 'GROUP_THEME',
            'attachment'      => json_encode(array_merge([
                'media' => $this->prepareMedia($attachments),
            ], $this->prepareData($data))),
            'format'          => 'json'
        ];

        $sig                    = md5($this->arInStr($params) . md5("{$this->accessToken}{$this->privateKey}"));
        $params['access_token'] = $this->accessToken;
        $params['sig']          = $sig;
        $result                 = json_decode($this->getUrl($this->apiUrl, 'POST', $params), true);

        if (isset($result['error_code']) && 5000 === (int)$result['error_code']) {
            $this->getUrl($this->apiUrl, 'POST', $params);
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $result = [];

        foreach ($data as $field => $datum) {
            if (in_array($field, ['publishAt', 'onBehalfOfGroup', 'disableComments'])) {
                $result[$field] = $datum;
            }
        }

        return $result;
    }

    /**
     * @param array $attachments
     * @return array
     */
    private function prepareMedia(array $attachments)
    {
        $attachData = [];
        foreach ($attachments as $type => $attachment) {
            switch ($type) {
                case 'text':
                    $attachData[] = [
                        'type' => 'text',
                        'text'   => $attachment
                    ];

                    break;
                case 'link':
                    $attachData[] = [
                        'type' => 'link',
                        'url'   => $attachment
                    ];

                    break;
            }
        }

        return $attachData;
    }

    /**
     * @param $url
     * @param string $type
     * @param array $params
     * @param int $timeout
     * @return bool|string
     */
    private function getUrl($url, $type = 'GET', $params = [], $timeout = 30)
    {
        if ($ch = curl_init()) {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            if ($type == 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Bot (http://bazarbratsk.ru)');
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }

        return '{}';
    }

    /**
     * @param array $params
     * @return string
     */
    private function arInStr(array $params)
    {
        ksort($params);

        $string = '';
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                $string .= $key . '=' . $this->arInStr($val);
            } else {
                $string .= $key . '=' . $val;
            }
        }
        return $string;
    }
}
