<?php

namespace horstoeko\orderx\tests;

use ReflectionClass;
use ReflectionProperty;
use \PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class TestCase extends PhpUnitTestCase
{
    /**
     * Registered files that should be deleted in test case teardown
     *
     * @var array
     */
    protected static $registeredTestCaseFiles = [];

    /**
     * Registered files that should be deleted in test teardown
     *
     * @var array
     */
    protected $registeredTestFiles = [];

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        self::$registeredTestCaseFiles = [];
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        foreach (self::$registeredTestCaseFiles as $registeredTestCaseFile) {
            if (file_exists($registeredTestCaseFile) && is_writeable($registeredTestCaseFile)) {
                @unlink($registeredTestCaseFile);
            }
        }

        self::$registeredTestCaseFiles = [];
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->registeredTestFiles = [];
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        foreach ($this->registeredTestFiles as $registeredTestFile) {
            if (file_exists($registeredTestFile) && is_writeable($registeredTestFile)) {
                @unlink($registeredTestFile);
            }
        }

        $this->registeredTestFiles = [];
    }

    /**
     * Register a file to delete in testcase teardown
     *
     * @param string $filename
     * @return void
     */
    public function registerFileForTestCaseTeardown(string $filename): void
    {
        self::$registeredTestCaseFiles[] = $filename;
    }

    /**
     * Register a file to delete in testmethod teardown
     *
     * @param string $filename
     * @return void
     */
    public function registerFileForTestMethodTeardown(string $filename): void
    {
        $this->registeredTestFiles[] = $filename;
    }

    /**
     * Expect notice on php version smaller than 8
     * Expect warning on php version greater or equal than 8
     *
     * @return void
     */
    public function expectNoticeOrWarning(): void
    {
        if (version_compare(phpversion(), '8', '>=')) {
            $this->expectWarning();
        } else {
            $this->expectNotice();
        }
    }

    /**
     * Access to private properties
     *
     * @param string $className
     * @param string $propertyName
     * @return ReflectionProperty
     */
    public function getPrivatePropertyFromClassname($className, $propertyName): ReflectionProperty
    {
        $reflector = new ReflectionClass($className);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);
        return $property;
    }

    /**
     * Access to private properties
     *
     * @param object $object
     * @param string $propertyName
     * @return ReflectionProperty
     */
    public function getPrivatePropertyFromObject($object, $propertyName): ReflectionProperty
    {
        $reflector = new ReflectionClass($object);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);
        return $property;
    }

    /**
     * Create a dummy DateTime instance
     *
     * @return \DateTime
     */
    public function getDummyDateTime(): \DateTime
    {
        return \DateTime::createFromFormat("Ymd", "20221231");
    }
}