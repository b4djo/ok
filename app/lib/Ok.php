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
     * @param string$url
     * @param string $type
     * @param array $params
     * @param int $timeout
     * @param bool $image
     * @param bool $decode
     * @return bool|string|null
     */
    private function getUrl(string $url, string $type = 'GET', array $params = [], int $timeout = 30, bool $image = false, bool $decode = true)
    {
        if ($ch = curl_init()) {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);

            if ('POST' === (string)$type) {
                curl_setopt($ch, CURLOPT_POST, true);

                if ($image) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                } elseif ($decode) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                }
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Bot');
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            $data = curl_exec($ch);

            curl_close($ch);

            return $data;

        }

        return null;
    }

    /**
     * @param int $groupId
     * @param string $message
     * @return bool
     */
    public function postGroupWall(int $groupId, string $message): bool
    {
        $message = str_replace("\n", "\\n", $message);

        $attachment = ['media' => [
            'type' => 'text',
            'text' => $message
        ]];

        $params = [
            'application_key' => $this->publicKey,
            'method'          => 'mediatopic.post',
            'gid'             => $groupId,
            'format'          => 'json',
            'attachment'      => json_encode($attachment),
            'type'            => 'GROUP_THEME',
        ];

        $sig = md5(http_build_query($params) . md5($this->accessToken . $this->privateKey));

        $params['access_token'] = $this->accessToken;
        $params['sig']          = $sig;

        $response = json_decode($this->getUrl('https://api.ok.ru/fb.do', 'POST', $params, 30, false, false), true);

        if (isset($response['error_code'])) {
            exit();
        }

        return true;
    }
}
