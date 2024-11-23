<?php
declare(strict_types=1);

namespace Unit\Controller;

use Laminas\Http\Response;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Visus\CustomerTfa\Controller\ResponseFactory;

class ResponseFactoryTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|MockObject
     */
    private ObjectManagerInterface|MockObject $objectManagerMock;

    /**
     * @var ResponseFactory
     */
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->factory = new ResponseFactory($this->objectManagerMock);
    }

    public function testCreate(): void
    {
        $result = $this->createMock(Json::class);

        $this->objectManagerMock->expects(self::once())
            ->method('create')
            ->with(Json::class)
            ->willReturn($result);

        $result->expects(self::once())
            ->method('setData')
            ->willReturn(['success' => true]);

        $result->expects(self::once())
            ->method('setHttpResponseCode')
            ->with(Response::STATUS_CODE_200)
            ->willReturn(Response::STATUS_CODE_200);

        $this->assertEquals($result, $this->factory->create(['success' => true]));
    }

    public function testCreateEmpty(): void
    {
        $result = $this->createMock(Raw::class);

        $this->objectManagerMock->expects(self::once())
            ->method('create')
            ->with(Raw::class)
            ->willReturn($result);

        $result->expects(self::once())
            ->method('setHttpResponseCode')
            ->with(Response::STATUS_CODE_204)
            ->willReturn(Response::STATUS_CODE_204);

        $this->assertEquals($result, $this->factory->create());
    }

    public function testCreateSetEmpty(): void
    {
        $result = $this->createMock(Raw::class);

        $this->objectManagerMock->expects(self::once())
            ->method('create')
            ->with(Raw::class)
            ->willReturn($result);

        $result->expects(self::once())
            ->method('setHttpResponseCode')
            ->with(Response::STATUS_CODE_204)
            ->willReturn(Response::STATUS_CODE_204);

        $this->assertEquals($result, $this->factory->create(['success' => true], Response::STATUS_CODE_204));
    }
}
