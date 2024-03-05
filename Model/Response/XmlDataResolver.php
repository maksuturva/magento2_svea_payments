<?php

namespace Svea\SveaPayment\Model\Response;

use Magento\Framework\Serialize\SerializerInterface;

class XmlDataResolver
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @param array|string $xml
     * @return array
     */
    public function resolveXmlData($xml): array
    {
        if (\is_array($xml)) {
            $xml = \reset($xml);
        }

        return (array)$this->serializer->unserialize(
            $this->serializer->serialize(\simplexml_load_string($xml))
        );
    }
}
