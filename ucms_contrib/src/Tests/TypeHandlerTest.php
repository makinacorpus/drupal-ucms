<?php

namespace MakinaCorpus\Ucms\Contrib\Tests;


use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;

class TypeHandlerTest extends AbstractDrupalTest
{
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * Determine if two associative arrays are similar
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     *
     * Taken from http://stackoverflow.com/a/3843768/848811
     *
     * @param array $a
     * @param array $b
     * @return bool
     */
    public function assertArrayAreSimilar($a, $b)
    {
        // if the indexes don't match, return immediately
        if (count(array_diff_assoc($a, $b))) {
            return false;
        }
        // we know that the indexes, but maybe not values, match.
        // compare the values between the two arrays
        foreach ($a as $k => $v) {
            if ($v !== $b[$k]) {
                return false;
            }
        }

        // we have identical indexes, and no unequal values
        return true;
    }

    public function testTypeHandler()
    {
        $typeHandler = $this->getMockBuilder('\MakinaCorpus\Ucms\Contrib\TypeHandler')
                            ->setMethods(
                                array(
                                    'getEditorialContentTypes',
                                    'getComponentTypes',
                                    'getContentTypes',
                                    'getMediaTypes',
                                )
                            )
                            ->getMock();

        // Mocking values
        $editorial = ['editorial_foo', 'editorial_bar'];
        $typeHandler->method('getEditorialContentTypes')
                    ->willReturn($editorial);
        $components = ['component_foo', 'component_bar'];
        $typeHandler->method('getComponentTypes')
                    ->willReturn($components);
        $typeHandler->method('getContentTypes')
                    ->willReturn(array_merge($components, $editorial));
        $media = ['media_foo', 'media_bar'];
        $typeHandler->method('getMediaTypes')
                    ->willReturn($media);

        // Testing functions
        $this->assertArrayAreSimilar($typeHandler->getAllTypes(), array_merge($editorial, $components, $media));
        $this->assertArrayAreSimilar($typeHandler->getEditorialTypes(), array_merge($editorial, $media));
        $this->assertArrayAreSimilar($typeHandler->getTabTypes('media'), $media);
        $this->assertArrayAreSimilar($typeHandler->getTabTypes('content'), array_merge($components, $editorial));

        $this->expectException(\Exception::class);
        $typeHandler->getTabTypes('foo');

        // @Todo test human readable names
    }
}
