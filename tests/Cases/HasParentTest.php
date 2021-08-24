<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Database\Model\Model;
use TreasureBag\Parental\HasParent;

class HasParentTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function childModelHasTableNameOfParentModel()
    {
        $this->assertEquals('parent_models', (new ParentModel)->getTable());
        $this->assertEquals('parent_models', (new ChildModel)->getTable());
        $this->assertEquals('child_model_without_traits', (new ChildModelWithoutTrait)->getTable());
    }

    /**
     * @test
     */
    function childModelHasSameForeignKeyAsParent()
    {
        $this->assertEquals('parent_model_id', (new ParentModel)->getForeignKey());
        $this->assertEquals('parent_model_id', (new ChildModel)->getForeignKey());
        $this->assertEquals('child_model_without_trait_id', (new ChildModelWithoutTrait)->getForeignKey());
    }

    /**
     * @test
     */
    function childModelHasSamePivotTableNameAsParent()
    {
        $related = new RelatedModel;

        $this->assertEquals('parent_model_related_model', (new ParentModel)->joiningTable($related));
        $this->assertEquals('parent_model_related_model', (new ChildModel)->joiningTable($related));
        $this->assertEquals('child_model_without_trait_related_model', (new ChildModelWithoutTrait)->joiningTable($related));
    }
}

class ParentModel extends Model
{

}

class ChildModel extends ParentModel
{
    use HasParent;
}

class ChildModelWithoutTrait extends ParentModel
{

}

class RelatedModel extends Model
{

}