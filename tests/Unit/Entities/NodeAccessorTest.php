<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Tests\Unit\Entities;

use MyDev\AuditRoutes\Entities\NodeAccessor;
use MyDev\AuditRoutes\Tests\Stubs\DummyTestFile;
use MyDev\AuditRoutes\Utilities\ClassDiscovery;
use MyDev\AuditRoutes\Visitors\CallbackVisitor;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeAbstract;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class NodeAccessorTest extends TestCase
{
    /** @var NodeAbstract $node */
    protected NodeAbstract $node;

    /** @throws ReflectionException */
    public function setUp(): void
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $parsedSource = $parser->parse(ClassDiscovery::source(DummyTestFile::class));

        if (!is_array($parsedSource) || empty($parsedSource)) {
            throw new \RuntimeException('Failed to parse DummyTestFile source');
        }

        $this->node = end($parsedSource);
    }

    #[Test]
    public function it_returns_the_name_property_when_node_has_name(): void
    {
        $node = $this->createNode('MyNode');
        $nodeAccessor = new NodeAccessor($node);

        $this->assertSame('MyNode', $nodeAccessor->getName());
    }

    #[Test]
    public function it_can_handle_name_properties_which_is_not_a_string(): void
    {
        $node = $this->createNode(new Node\Identifier('MyNode'));
        $nodeAccessor = new NodeAccessor($node);

        $this->assertSame('MyNode', $nodeAccessor->getName());
    }

    #[Test]
    public function it_traverses_with_node_visitors(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);
        $visited = false;

        /** @phpstan-ignore-next-line  */
        $visitor = new CallbackVisitor(function (Node $node) use (&$visited): void {
            $visited = true;
        });

        $nodeAccessor->traverse($visitor);

        $this->assertTrue($visited);
    }

    #[Test]
    public function it_filters_child_nodes_by_class(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);

        $results = $nodeAccessor->filter(Node\Attribute::class);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(NodeAccessor::class, $result);
            $this->assertContains($result->getName(), ['DataProvider', 'Test']);
        }
    }

    #[Test]
    public function it_filters_nodes_with_closure(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);

        /** @phpstan-ignore-next-line  */
        $results = $nodeAccessor->filter(fn (ClassMethod $node): bool => strval($node->name) === 'getValue');

        $this->assertCount(1, $results);
        $this->assertInstanceOf(NodeAccessor::class, $results[0]);
        $this->assertSame('getValue', $results[0]->getName());
    }

    #[Test]
    public function has_returns_true_if_node_does_match_filter(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);

        $this->assertTrue($nodeAccessor->has(Return_::class));
    }

    #[Test]
    public function has_returns_false_if_node_does_not_match_filter(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);

        $this->assertFalse($nodeAccessor->has(Node\Stmt\Declare_::class));
    }

    #[Test]
    public function find_returns_the_first_instance_for_a_match_on_instance(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);

        $found = $nodeAccessor->find(ClassMethod::class);

        $this->assertInstanceOf(NodeAccessor::class, $found);
        $this->assertSame('dummy_test_method', $found->getName());
    }

    #[Test]
    public function find_returns_the_first_instance_for_a_match_on_closure(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);

        $found = $nodeAccessor->find(function (NodeAbstract $node): bool {
            if (!property_exists($node, 'name')) {
                return false;
            }

            return strval($node->name) === 'DummyTestFile';
        });

        $this->assertInstanceOf(NodeAccessor::class, $found);
        $this->assertSame('DummyTestFile', $found->getName());
    }

    #[Test]
    public function find_returns_null_if_no_match(): void
    {
        $node = $this->createNode();
        $nodeAccessor = new NodeAccessor($node);

        $found = $nodeAccessor->find(Node\Stmt\Declare_::class);

        $this->assertNull($found);
    }

    #[Test]
    public function find_applies_multiple_filters_in_nested_sequence(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);

        $found = $nodeAccessor->find(Return_::class, Node\Expr\Array_::class);

        $this->assertInstanceOf(NodeAccessor::class, $found);
        $this->assertInstanceOf(Node\Expr\Array_::class, $found->getNode());
    }

    #[Test]
    public function each_executes_callback_on_matching_nodes(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);
        $foundNames = [];

        /** @phpstan-ignore-next-line  */
        $nodeAccessor->each(function (Node\Attribute $node) use (&$foundNames) {
            $foundNames[] = strval($node->name);
        });

        $this->assertEquals(['DataProvider', 'Test'], $foundNames);
    }

    #[Test]
    public function each_does_not_execute_callback_on_non_matching_nodes(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);
        $executed = false;

        /** @phpstan-ignore-next-line  */
        $nodeAccessor->each(function (Node\Stmt\Declare_ $node) use (&$executed): void {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    #[Test]
    public function each_handles_closure_with_no_parameters(): void
    {
        $nodeAccessor = new NodeAccessor($this->node);
        $executed = false;

        $nodeAccessor->each(function () use (&$executed): void {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /**
     * @param Node\Identifier|string|null $name
     * @return NodeAbstract
     */
    private function createNode(Node\Identifier | string | null $name = 'Default'): NodeAbstract
    {
        return new Node\Stmt\Class_($name, [
            'stmts' => [
                new ClassMethod('myMethod', [
                    'stmts' => [
                        new Return_(),
                    ],
                ]),
            ],
        ]);
    }
}
