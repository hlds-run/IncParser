<?php

namespace Albertio\IncParser;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\AddOn\StepRunnerUninstallTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait,
        StepRunnerUpgradeTrait,
        StepRunnerUninstallTrait;
        
    public function installStep1()
    {
    }

    public function upgrade(array $stepParams = [])
    {
        return [];
    }

    public function uninstallStep1()
    {
    }
}