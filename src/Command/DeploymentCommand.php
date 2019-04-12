<?php declare(strict_types=1);

namespace TeamDeployment\Plugin\Deployment\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class DeploymentCommand
 * @package TeamDeployment\Plugin\Deployment\Command
 */
class DeploymentCommand extends Command
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $pluginRepository;

    /**
     * DeploymentCommand constructor.
     * @param string $name
     * @param EntityRepositoryInterface $pluginRepository
     */
    public function __construct(string $name, EntityRepositoryInterface $pluginRepository)
    {
        parent::__construct($name);
        $this->pluginRepository = $pluginRepository;
        $this->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Ask to install or update every plugin managed by composer');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     * @throws \ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('plugin:refresh');
        $command->run(new ArrayInput([]), new NullOutput());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('managedByComposer', 1));
        $plugins = $this->pluginRepository->search($criteria, Context::createDefaultContext());

        /** @var Plugin\PluginEntity $plugin */
        foreach ($plugins as $plugin) {
            $pluginName = (new \ReflectionClass($plugin->getName()))->getShortName();

            // Check if plugin is installed, otherwise if an update is available
            if (!$plugin->getInstalledAt()) {
                if ($this->askQuestion('Install and activate ' . $pluginName . ' ?(Y/n) ', $input, $output)) {
                    $this->installPlugin($pluginName, $output);
                }
            } elseif ($plugin->getUpgradeVersion()) {
                if ($this->askQuestion('Update ' . $pluginName . '? (Y/n) ', $input, $output)) {
                    $this->updatePlugin($pluginName, $output);
                }
            }
        }
    }

    /**
     * @param string $questionText
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     */
    private function askQuestion(string $questionText, InputInterface $input, OutputInterface $output): bool
    {
        if (!$input->getOption('interactive')) {
            return true;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion($questionText, true, '/^(y|j)/i');

        return $helper->ask($input, $output, $question);
    }

    /**
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     */
    private function installPlugin(string $pluginName, OutputInterface $output)
    {
        $arguments = new ArrayInput(['plugins' => [$pluginName], '--activate' => true]);
        $command = $this->getApplication()->find('plugin:install');
        $command->run($arguments, $output);
    }

    /**
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     */
    private function updatePlugin(string $pluginName, OutputInterface $output)
    {
        $arguments = new ArrayInput(['plugins' => [$pluginName]]);
        $command = $this->getApplication()->find('plugin:update');
        $command->run($arguments, $output);
    }
}
