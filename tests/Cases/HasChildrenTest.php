<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Database\Model\Model;
use TreasureBag\Parental\HasChildren;

class HasChildrenTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function childModelMutatorsAreNotInstigated()
    {
        $model = (new HasChildrenParentModel())->newFromBuilder([
            'type' => HasChildrenChildModel::class
        ]);

        $this->assertEquals(true, $model->echoResult());
    }
}

class HasChildrenParentModel extends Model
{
    use HasChildren;

    protected $fillable = ['type'];

    public function echoResult(): bool
    {
        return false;
    }
}

class HasChildrenChildModel extends HasChildrenParentModel
{
    public function echoResult(): bool
    {
        return true;
    }
}