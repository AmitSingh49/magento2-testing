<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogUrlRewrite\Test\Unit\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Store\Model\Store;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;

/**
 * Class AfterImportDataObserverTest
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AfterImportDataObserverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $categoryId = 10;

    /**
     * @var \Magento\UrlRewrite\Model\UrlPersistInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlPersist;

    /**
     * @var \Magento\UrlRewrite\Model\UrlFinderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlFinder;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productUrlRewriteGenerator;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productRepository;

    /**
     * @var \Magento\CatalogImportExport\Model\Import\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $importProduct;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $observer;

    /**
     * @var \Magento\Framework\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var \Magento\Catalog\Model\ProductFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $catalogProductFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\ObjectRegistryFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectRegistryFactory;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productUrlPathGenerator;

    /**
     * @var \Magento\CatalogUrlRewrite\Service\V1\StoreViewService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeViewService;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavConfig;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resource;

    /**
     * @var \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $select;

    /**
     * @var \Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlRewriteFactory;

    /**
     * @var \Magento\UrlRewrite\Service\V1\Data\UrlRewrite|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlRewrite;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\ObjectRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectRegistry;

    /**
     * @var \Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $importMock;

    /**
     * @var \Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver
     */
    protected $import;

    /**
     * @var \Magento\Catalog\Model\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $product;

    /**
     * Test products returned by getBunch method of event object.
     *
     * @var array
     */
    protected $products = [
        [
            'sku' => 'sku',
            'url_key' => 'value1',
            ImportProduct::COL_STORE => Store::DEFAULT_STORE_ID,
        ],
        [
            'sku' => 'sku3',
            'url_key' => 'value3',
            ImportProduct::COL_STORE => 'not global',
        ],
    ];

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.TooManyFields)
     * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function setUp()
    {
        $this->importProduct = $this->getMock(
            '\Magento\CatalogImportExport\Model\Import\Product',
            [
                'getNewSku',
                'getProductCategories',
                'getProductWebsites',
                'getStoreIdByCode',
                'getCategoryProcessor',
            ],
            [],
            '',
            false
        );
        $this->catalogProductFactory = $this->getMock(
            '\Magento\Catalog\Model\ProductFactory',
            [
                'create',
            ],
            [],
            '',
            false
        );
        $this->storeManager = $this
            ->getMockBuilder(
                '\Magento\Store\Model\StoreManagerInterface'
            )
            ->disableOriginalConstructor()
            ->setMethods([
                'getWebsite',
            ])
            ->getMockForAbstractClass();
        $this->event = $this->getMock('\Magento\Framework\Event', ['getAdapter', 'getBunch'], [], '', false);
        $this->event->expects($this->any())->method('getAdapter')->willReturn($this->importProduct);
        $this->event->expects($this->any())->method('getBunch')->willReturn($this->products);
        $this->observer = $this->getMock('\Magento\Framework\Event\Observer', ['getEvent'], [], '', false);
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->urlPersist = $this->getMockBuilder('\Magento\UrlRewrite\Model\UrlPersistInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productUrlRewriteGenerator =
            $this->getMockBuilder('\Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator')
                ->disableOriginalConstructor()
                ->setMethods(['generate'])
                ->getMock();
        $this->productRepository = $this->getMockBuilder('\Magento\Catalog\Api\ProductRepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavConfig = $this->getMock(
            '\Magento\Eav\Model\Config',
            [
                'getAttribute',
            ],
            [],
            '',
            false
        );
        $attribute = $this->getMockBuilder('\Magento\Eav\Model\Entity\Attribute\AbstractAttribute')
            ->setMethods([
                'getBackendTable',
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $beTable = 'backend table';
        $attribute->expects($this->any())
            ->method('getBackendTable')
            ->willReturn($beTable);
        $this->eavConfig->expects($this->any())
            ->method('getAttribute')
            ->with(
                \Magento\Catalog\Model\Product::ENTITY,
                \Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver::URL_KEY_ATTRIBUTE_CODE
            )
            ->willReturn($attribute);

        $this->resource = $this->getMock(
            '\Magento\Framework\App\ResourceConnection',
            [],
            [],
            '',
            false
        );
        $this->connection = $this->getMockBuilder('\Magento\Framework\DB\Adapter\AdapterInterface')
            ->disableOriginalConstructor()
            ->setMethods([
                'quoteInto',
                'select',
                'fetchAll',
            ])
            ->getMockForAbstractClass();
        $this->resource
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->select = $this->getMock(
            '\Magento\Framework\DB\Select',
            [
                'from',
                'where',
            ],
            [],
            '',
            false
        );
        $this->connection
            ->expects($this->any())
            ->method('select')
            ->willReturn($this->select);
        $this->objectRegistryFactory = $this->getMock(
            '\Magento\CatalogUrlRewrite\Model\ObjectRegistryFactory',
            [],
            [],
            '',
            false
        );
        $this->productUrlPathGenerator = $this->getMock(
            '\Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator',
            [],
            [],
            '',
            false
        );
        $this->storeViewService = $this->getMock(
            '\Magento\CatalogUrlRewrite\Service\V1\StoreViewService',
            [],
            [],
            '',
            false
        );
        $this->urlRewriteFactory = $this->getMock(
            '\Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory',
            [
                'create',
            ],
            [],
            '',
            false
        );
        $this->urlFinder = $this
            ->getMockBuilder('\Magento\UrlRewrite\Model\UrlFinderInterface')
            ->setMethods([
                'findAllByData',
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->urlRewrite = $this
            ->getMockBuilder('Magento\UrlRewrite\Service\V1\Data\UrlRewrite')
            ->disableOriginalConstructor()
            ->getMock();

        $this->product = $this
            ->getMockBuilder('Magento\Catalog\Model\Product')
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectRegistry = $this
            ->getMockBuilder('\Magento\CatalogUrlRewrite\Model\ObjectRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryProcessor = $this->getMock(
            '\Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor',
            [
                'getCategoryById',
            ],
            [],
            '',
            false
        );
        $category = $this->getMock(
            'Magento\Catalog\Model\Category',
            [
                'getId',
            ],
            [],
            '',
            false
        );
        $category
            ->expects($this->any())
            ->method('getId')
            ->willReturn($this->categoryId);
        $categoryProcessor
            ->expects($this->any())
            ->method('getCategoryById')
            ->with($this->categoryId)
            ->willReturn($category);
        $this->importProduct
            ->expects($this->any())
            ->method('getCategoryProcessor')
            ->willReturn($categoryProcessor);

        $this->objectManager = new ObjectManager($this);
        $this->import = $this->objectManager->getObject(
            '\Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver',
            [
                'catalogProductFactory' => $this->catalogProductFactory,
                'eavConfig' => $this->eavConfig,
                'objectRegistryFactory' => $this->objectRegistryFactory,
                'productUrlPathGenerator' => $this->productUrlPathGenerator,
                'resource' => $this->resource,
                'storeViewService' => $this->storeViewService,
                'storeManager'=> $this->storeManager,
                'urlPersist' => $this->urlPersist,
                'urlRewriteFactory' => $this->urlRewriteFactory,
                'urlFinder' => $this->urlFinder,
            ]
        );
    }

    /**
     * Test for afterImportData()
     * Covers afterImportData() + protected methods used inside except related to generateUrls() ones.
     * generateUrls will be covered separately.
     *
     * @covers \Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver::afterImportData
     * @covers \Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver::_populateForUrlGeneration
     * @covers \Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver::isGlobalScope
     * @covers \Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver::populateGlobalProduct
     * @covers \Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver::addProductToImport
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAfterImportData()
    {
        $newSku = ['entity_id' => 'value'];
        $websiteId = 'websiteId value';
        $productsCount = count($this->products);
        $websiteMock = $this->getMock(
            '\Magento\Store\Model\Website',
            [
                'getStoreIds',
            ],
            [],
            '',
            false
        );
        $storeIds = [1, Store::DEFAULT_STORE_ID];
        $websiteMock
            ->expects($this->once())
            ->method('getStoreIds')
            ->willReturn($storeIds);
        $this->storeManager
            ->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);
        $this->importProduct
            ->expects($this->exactly($productsCount))
            ->method('getNewSku')
            ->withConsecutive(
                [$this->products[0][ImportProduct::COL_SKU]],
                [$this->products[1][ImportProduct::COL_SKU]]
            )
            ->willReturn($newSku);
        $this->importProduct
            ->expects($this->exactly($productsCount))
            ->method('getProductCategories')
            ->withConsecutive(
                [$this->products[0][ImportProduct::COL_SKU]],
                [$this->products[1][ImportProduct::COL_SKU]]
            );
        $getProductWebsitesCallsCount = $productsCount*2;
        $this->importProduct
            ->expects($this->exactly($getProductWebsitesCallsCount))
            ->method('getProductWebsites')
            ->willReturn([
                $newSku['entity_id'] => $websiteId,
            ]);
        $map = [
            [$this->products[0][ImportProduct::COL_STORE], $this->products[0][ImportProduct::COL_STORE]],
            [$this->products[1][ImportProduct::COL_STORE], $this->products[1][ImportProduct::COL_STORE]]
        ];
        $this->importProduct
            ->expects($this->exactly(1))
            ->method('getStoreIdByCode')
            ->will($this->returnValueMap($map));
        $product = $this->getMock(
            '\Magento\Catalog\Model\Product',
            [
                'getId',
                'setId',
                'getSku',
                'setStoreId',
                'getStoreId',
            ],
            [],
            '',
            false
        );
        $product
            ->expects($this->exactly($productsCount))
            ->method('setId')
            ->with($newSku['entity_id']);
        $product
            ->expects($this->any())
            ->method('getId')
            ->willReturn($newSku['entity_id']);
        $product
            ->expects($this->exactly($productsCount))
            ->method('getSku')
            ->will($this->onConsecutiveCalls(
                $this->products[0]['sku'],
                $this->products[1]['sku']
            ));
        $product
            ->expects($this->exactly($productsCount))
            ->method('getStoreId')
            ->will($this->onConsecutiveCalls(
                $this->products[0][ImportProduct::COL_STORE],
                $this->products[1][ImportProduct::COL_STORE]
            ));
        $product
            ->expects($this->once())
            ->method('setStoreId')
            ->with($this->products[1][ImportProduct::COL_STORE]);
        $this->catalogProductFactory
            ->expects($this->exactly($productsCount))
            ->method('create')
            ->willReturn($product);
        $this->connection
            ->expects($this->exactly(4))
            ->method('quoteInto')
            ->withConsecutive(
                [
                    '(store_id = ?',
                    $storeIds[0],
                ],
                [
                    ' AND entity_id = ?)',
                    $newSku['entity_id'],
                ]
            );

        $productUrls = [
            'url 1',
            'url 2',
        ];

        $importMock = $this->getImportMock([
            'generateUrls',
            'canonicalUrlRewriteGenerate',
            'categoriesUrlRewriteGenerate',
            'currentUrlRewritesRegenerate',
            'cleanOverriddenUrlKey',
        ]);
        $importMock
            ->expects($this->once())
            ->method('generateUrls')
            ->willReturn($productUrls);
        $this->urlPersist
            ->expects($this->once())
            ->method('replace')
            ->with($productUrls);

        $importMock->execute($this->observer);
    }

    /**
     * Cover cleanOverriddenUrlKey().
     */
    public function testCleanOverriddenUrlKey()
    {
        $urlKeyAttributeBackendTable = 'table value';
        $urlKeyAttributeId = 'id value';
        $entityStoresToCheckOverridden = [1,2,3];
        $this->import->urlKeyAttributeBackendTable = $urlKeyAttributeBackendTable;
        $this->import->urlKeyAttributeId = $urlKeyAttributeId;
        $this->setPropertyValue($this->import, 'entityStoresToCheckOverridden', $entityStoresToCheckOverridden);
        $this->select
            ->expects($this->once())
            ->method('from')
            ->with(
                $urlKeyAttributeBackendTable,
                ['store_id', 'entity_id']
            )
            ->will($this->returnSelf());
        $this->select
            ->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                [
                    'attribute_id = ?',
                    $urlKeyAttributeId,
                ],
                [
                    implode(' OR ', $entityStoresToCheckOverridden)
                ]
            )
            ->will($this->returnSelf());

        $entityIdVal = 'entity id value';
        $storeIdVal = 'store id value';
        $entityStore = [
            'entity_id' => $entityIdVal,
            'store_id' => $storeIdVal,
        ];
        $entityStoresToClean = [$entityStore];
        $products = [
            $entityIdVal => [
                $storeIdVal => 'value',
            ]
        ];
        $this->setPropertyValue($this->import, 'products', $products);
        $this->connection
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($entityStoresToClean);

        $actualResult = $this->invokeMethod($this->import, 'cleanOverriddenUrlKey');
        $this->assertEquals($this->import, $actualResult);
    }

    /**
     * Cover cleanOverriddenUrlKey() method with empty entityStoresToCheckOverridden property.
     */
    public function testCleanOverriddenUrlKeyEmptyEntityStoresToCheckOverridden()
    {
        $this->setPropertyValue($this->import, 'entityStoresToCheckOverridden', null);
        $this->select
            ->expects($this->never())
            ->method('from');
        $this->select
            ->expects($this->never())
            ->method('where');

        $actualResult = $this->invokeMethod($this->import, 'cleanOverriddenUrlKey');
        $this->assertEquals($this->import, $actualResult);
    }

    /**
     * Cover canonicalUrlRewriteGenerate().
     */
    public function testCanonicalUrlRewriteGenerateWithUrlPath()
    {
        $productId = 'product_id';
        $requestPath = 'simple-product.html';
        $storeId = 10;
        $product = $this
            ->getMockBuilder('Magento\Catalog\Model\Product')
            ->disableOriginalConstructor()
            ->getMock();
        $productsByStores = [$storeId => $product];
        $products = [
            $productId => $productsByStores,
        ];

        $targetPath = 'catalog/product/view/id/' . $productId;
        $this->setPropertyValue($this->import, 'products', $products);

        $this->productUrlPathGenerator
            ->expects($this->once())
            ->method('getUrlPathWithSuffix')
            ->will($this->returnValue($requestPath));
        $this->productUrlPathGenerator
            ->expects($this->once())
            ->method('getUrlPath')
            ->will($this->returnValue('urlPath'));
        $this->productUrlPathGenerator
            ->expects($this->once())
            ->method('getCanonicalUrlPath')
            ->will($this->returnValue($targetPath));
        $this->urlRewrite
            ->expects($this->once())
            ->method('setStoreId')
            ->with($storeId)
            ->will($this->returnSelf());
        $this->urlRewrite
            ->expects($this->once())
            ->method('setEntityId')
            ->with($productId)
            ->will($this->returnSelf());
        $this->urlRewrite
            ->expects($this->once())
            ->method('setEntityType')
            ->with(ProductUrlRewriteGenerator::ENTITY_TYPE)
            ->will($this->returnSelf());
        $this->urlRewrite
            ->expects($this->once())
            ->method('setRequestPath')
            ->with($requestPath)
            ->will($this->returnSelf());
        $this->urlRewrite
            ->expects($this->once())
            ->method('setTargetPath')
            ->with($targetPath)
            ->will($this->returnSelf());
        $this->urlRewriteFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->urlRewrite);

        $actualResult = $this->invokeMethod($this->import, 'canonicalUrlRewriteGenerate');
        $this->assertEquals(
            [
                $this->urlRewrite,
            ],
            $actualResult
        );
    }

    /**
     * Cover canonicalUrlRewriteGenerate().
     */
    public function testCanonicalUrlRewriteGenerateWithEmptyUrlPath()
    {
        $productId = 'product_id';
        $storeId = 10;
        $product = $this
            ->getMockBuilder('Magento\Catalog\Model\Product')
            ->disableOriginalConstructor()
            ->getMock();
        $productsByStores = [$storeId => $product];
        $products = [
            $productId => $productsByStores,
        ];

        $this->setPropertyValue($this->import, 'products', $products);

        $this->productUrlPathGenerator
            ->expects($this->once())
            ->method('getUrlPath')
            ->will($this->returnValue(''));
        $this->urlRewriteFactory
            ->expects($this->never())
            ->method('create');

        $actualResult = $this->invokeMethod($this->import, 'canonicalUrlRewriteGenerate');
        $this->assertEquals([], $actualResult);
    }

    /**
     * Cover categoriesUrlRewriteGenerate().
     */
    public function testCategoriesUrlRewriteGenerate()
    {
        $urlPathWithCategory = 'category/simple-product.html';
        $storeId = 10;
        $productId = 'product_id';
        $canonicalUrlPathWithCategory = 'canonical-path-with-category';
        $product = $this
            ->getMockBuilder('Magento\Catalog\Model\Product')
            ->disableOriginalConstructor()
            ->getMock();
        $productsByStores = [
            $storeId => $product,
        ];
        $products = [
            $productId => $productsByStores,
        ];
        $categoryCache = [
            $productId => [$this->categoryId],
        ];

        $this->setPropertyValue($this->import, 'products', $products);
        $this->setPropertyValue($this->import, 'categoryCache', $categoryCache);
        $this->setPropertyValue($this->import, 'import', $this->importProduct);

        $this->productUrlPathGenerator
            ->expects($this->any())
            ->method('getUrlPathWithSuffix')
            ->will($this->returnValue($urlPathWithCategory));
        $this->productUrlPathGenerator
            ->expects($this->any())
            ->method('getCanonicalUrlPath')
            ->will($this->returnValue($canonicalUrlPathWithCategory));
        $category = $this->getMock('Magento\Catalog\Model\Category', [], [], '', false);
        $category
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($this->categoryId));
        $this->urlRewrite
            ->expects($this->any())
            ->method('setStoreId')
            ->with($storeId)
            ->will($this->returnSelf());
        $this->urlRewrite
            ->expects($this->any())
            ->method('setEntityId')
            ->with($productId)
            ->will($this->returnSelf());
        $this->urlRewrite
            ->expects($this->any())
            ->method('setEntityType')
            ->with(ProductUrlRewriteGenerator::ENTITY_TYPE)
            ->will($this->returnSelf());
        $this->urlRewrite
            ->expects($this->any())
            ->method('setRequestPath')
            ->with($urlPathWithCategory)
            ->will($this->returnSelf());
        $this->urlRewrite
            ->expects($this->any())
            ->method('setTargetPath')
            ->with($canonicalUrlPathWithCategory)
            ->will($this->returnSelf());
        $this->urlRewrite
            ->expects($this->any())
            ->method('setMetadata')
            ->with(['category_id' => $this->categoryId])
            ->will($this->returnSelf());
        $this->urlRewriteFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->urlRewrite);

        $actualResult = $this->invokeMethod($this->import, 'categoriesUrlRewriteGenerate');
        $this->assertEquals(
            [
                $this->urlRewrite,
            ],
            $actualResult
        );
    }

    /**
     * @param \Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver $object
     * @param string $property
     * @param mixed $value
     * @return void
     */
    protected function setPropertyValue($object, $property, $value)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    /**
     * @param \Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver $object
     * @param string $methodName
     * @param array $parameters
     * @return mixed
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Get mock of Import class instance with defined methods and called constructor.
     */
    protected function getImportMock($methods = [])
    {
        return $this->getMock(
            '\Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver',
            $methods,
            [
                $this->catalogProductFactory,
                $this->eavConfig,
                $this->objectRegistryFactory,
                $this->productUrlPathGenerator,
                $this->resource,
                $this->storeViewService,
                $this->storeManager,
                $this->urlPersist,
                $this->urlRewriteFactory,
                $this->urlFinder,
            ],
            ''
        );
    }

    /**
     * @param mixed $storeId
     * @param mixed $productId
     * @param mixed $requestPath
     * @param mixed $targetPath
     * @param mixed $redirectType
     * @param mixed $metadata
     * @param mixed $description
     */
    protected function currentUrlRewritesRegeneratorPrepareUrlRewriteMock(
        $storeId,
        $productId,
        $requestPath,
        $targetPath,
        $redirectType,
        $metadata,
        $description
    ) {
        $this->urlRewrite->expects($this->any())->method('setStoreId')->with($storeId)
            ->will($this->returnSelf());
        $this->urlRewrite->expects($this->any())->method('setEntityId')->with($productId)
            ->will($this->returnSelf());
        $this->urlRewrite->expects($this->any())->method('setEntityType')
            ->with(ProductUrlRewriteGenerator::ENTITY_TYPE)->will($this->returnSelf());
        $this->urlRewrite->expects($this->any())->method('setRequestPath')->with($requestPath)
            ->will($this->returnSelf());
        $this->urlRewrite->expects($this->any())->method('setTargetPath')->with($targetPath)
            ->will($this->returnSelf());
        $this->urlRewrite->expects($this->any())->method('setIsAutogenerated')->with(0)
            ->will($this->returnSelf());
        $this->urlRewrite->expects($this->any())->method('setRedirectType')->with($redirectType)
            ->will($this->returnSelf());
        $this->urlRewrite->expects($this->any())->method('setMetadata')->with($metadata)
            ->will($this->returnSelf());
        $this->urlRewriteFactory->expects($this->any())->method('create')->will($this->returnValue($this->urlRewrite));
        $this->urlRewrite->expects($this->once())->method('setDescription')->with($description)
            ->will($this->returnSelf());
    }

    /**
     * @param array $currentRewrites
     * @return array
     */
    protected function currentUrlRewritesRegeneratorGetCurrentRewritesMocks($currentRewrites)
    {
        $rewrites = [];
        foreach ($currentRewrites as $urlRewrite) {
            /**
             * @var \PHPUnit_Framework_MockObject_MockObject
             */
            $url = $this->getMockBuilder('Magento\UrlRewrite\Service\V1\Data\UrlRewrite')
                ->disableOriginalConstructor()->getMock();
            foreach ($urlRewrite as $key => $value) {
                $url->expects($this->any())
                    ->method('get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))))
                    ->will($this->returnValue($value));
            }
            $rewrites[] = $url;
        }
        return $rewrites;
    }
}
