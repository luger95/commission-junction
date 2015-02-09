<?php namespace Ejarnutowski;


class CommissionJunction {

    private $devId;
    private $storeId;
    private $recordsPerPage = 100;

    private $urls = [
        'advertisers' => 'https://advertiser-lookup.api.cj.com/v3/advertiser-lookup?',
        'products'    => 'https://product-search.api.cj.com/v2/product-search?',
    ];

    private $options = [
        'advertisers' => [
            'advertiser-ids'            => null,
            'advertiser-name'           => null,
            'keywords'                  => null,
            'page-number'               => null,
            'mobile-tracking-certified' => null,
            'records-per-page'          => null,
        ],
        'products' => [
            'advertiser-ids'            => null,
            'keywords'                  => null,
            'serviceable-area'          => null,
            'isbn'                      => null,
            'upc'                       => null,
            'manufacturer-name'         => null,
            'manufacturer-sku'          => null,
            'advertiser-sku'            => null,
            'low-price'                 => null,
            'high-price'                => null,
            'low-sale-price'            => null,
            'high-sale-price'           => null,
            'currency'                  => null,
            'sort-by'                   => null,
            'sort-order'                => null,
            'page-number'               => null,
            'records-per-page'          => null,
        ],
    ];
    
    private $schema = [
        'advertisers' => [
            'account-status'            => '',
            'advertiser-id'             => '',
            'advertiser-name'           => '',
            'language'                  => '',
            'mobile-tracking-certified' => '',
            'network-rank'              => '',
            'performance-incentives'    => '',
            'program-url'               => '',
            'relationship-status'       => '',
            'seven-day-epc'             => '',
            'three-month-epc'           => '',
            'actions'                   => [],
            'link-types'                => [],
            'primary-category'          => [],
        ],
        'products' => [
            'ad-id'                     => '',
            'advertiser-id'             => '',
            'advertiser-name'           => '',
            'advertiser-category'       => '',
            'buy-url'                   => '',
            'catalog-id'                => '',
            'currency'                  => '',
            'description'               => '',
            'image-url'                 => '',
            'in-stock'                  => '',
            'isbn'                      => '',
            'manufacturer-name'         => '',
            'manufacturer-sku'          => '',
            'name'                      => '',
            'price'                     => '',
            'retail-price'              => '',
            'sale-price'                => '',
            'sku'                       => '',
            'upc'                       => '',
        ],
    ];

    public function __construct ($devId, $storeId)
    {
        $this->devId   = $devId;
        $this->storeId = $storeId;
        $this->urls['products'] .= 'website-id=' . $storeId;
    }

    public function allAdvertisers ()
    {
        return array_merge($this->joinedAdvertisers(), $this->notJoinedAdvertisers());
    }

    public function joinedAdvertisers ()
    {
        $options = [
            'advertiser-ids' => 'joined'
        ];

        return $this->getAdvertisers($options);
    }

    public function notJoinedAdvertisers ()
    {
        $options = [
            'advertiser-ids' => 'notjoined'
        ];

        return $this->getAdvertisers($options);
    }

    public function advertisersByCid ($cids)
    {
        if (!is_array($cids)) return false;

        $options = [
            'advertiser-ids' => implode(",", $cids)
        ];

        return $this->getAdvertisers($options);
    }

    public function advertisersByName ($name)
    {
        if (!is_string($name)) return false;

        $options = [
            'advertiser-name' => $name
        ];

        return $this->getAdvertisers($options);
    }

    public function advertisersByUrl ($url)
    {
        if (!is_string($url)) return false;

        $options = [
            'advertiser-name' => $url
        ];

        return $this->getAdvertisers($options);
    }

    public function advertisersByKeywords ($keywords)
    {
        if (!is_string($keywords)) return false;

        $options = [
            'keywords' => $keywords
        ];

        return $this->getAdvertisers($options);
    }

    private function getAdvertisers ($options)
    {
        $options = array_intersect_key($options, $this->options['advertisers']);

        $url = $this->constructUrl($this->urls['advertisers'], $options);
        $xml = $this->makeRequest($url);

        $obj         = json_decode(json_encode(simplexml_load_string($xml)), 1);
        $total       = $obj['advertisers']['@attributes']['total-matched'];
        $totalPages  = (int) ceil($total / $this->recordsPerPage);

        if ($total == 0) return [];

        $advertisers = $obj['advertisers']['advertiser'];

        // Commission Junction changes the schema of their response when only a
        // single record is returned, so we have to process the data differently.
        if ($total == 1)
        {
            $advertisers = [0 => $advertisers];
        }

        for ($page = 2; $page <= $totalPages; $page++)
        {
            $url = $this->constructUrl($this->urls['advertisers'], $options, $page);
            $xml = $this->makeRequest($url);

            $obj         = json_decode(json_encode(simplexml_load_string($xml)), 1);
            $advertisers = array_merge($advertisers, $obj['advertisers']['advertiser']);
        }

        foreach ($advertisers as &$advertiser)
        {
            $advertiser = array_merge($this->schema['advertisers'], $advertiser);
        }

        return $advertisers;
    }

    public function constructUrl ($url, $options, $pageNumber = 1)
    {
        $options['page-number']      = $pageNumber;
        $options['records-per-page'] = $this->recordsPerPage;

        foreach ($options as $key => $value)
        {
            $url .= '&' . $key . '=' . urlencode($value);
        }

        return $url;
    }

    private function makeRequest ($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPGET,        1);
        curl_setopt($ch, CURLOPT_HTTPHEADER,     ['authorization: ' . $this->devId]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

}