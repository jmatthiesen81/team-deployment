<?php declare(strict_types=1);

namespace TeamDeployment\Plugin\Deployment\Commands;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Deployment extends Command
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $pluginRepository;

    public function __construct(string $name, EntityRepositoryInterface $pluginRepository)
    {
        parent::__construct($name);
        $this->pluginRepository = $pluginRepository;
        $this->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Ask to install or update every plugin managed by composer');
    }

    /**
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     */
    public function question(string $pluginName, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Install and activate ' . $pluginName . ' ?(Y/n) ', true, '/^(y|j)/i');
        if ($helper->ask($input, $output, $question)) {
            $this->installPlugin($pluginName, $output);
        }
        $question = new ConfirmationQuestion('Update ' . $pluginName . '? (Y/n) ', true, '/^(y|j)/i');
        if ($helper->ask($input, $output, $question)) {
            $this->updatePlugin($pluginName, $output);
        }
    }

    /**
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     */
    public function installPlugin(string $pluginName, OutputInterface $output)
    {
        $arguments = new ArrayInput(['plugins' => [$pluginName], '--activate' => true]);
        $command = $this->getApplication()->find('plugin:install');
        $command->run($arguments, $output);
    }

    /**
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     */
    public function updatePlugin(string $pluginName, OutputInterface $output)
    {
        $arguments = new ArrayInput(['plugins' => [$pluginName]]);
        $command = $this->getApplication()->find('plugin:update');
        $command->run($arguments, $output);
    }

    /**
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('plugin:refresh');
        $command->run(new ArrayInput([]), $output);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('managedByComposer', 1));
        $plugins = $this->pluginRepository->search($criteria, Context::createDefaultContext());

        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            $pluginName = $plugin->getName();

            if ($input->getOption('interactive')) {
                $this->question($pluginName, $input, $output);
            } else {
                $this->installPlugin($pluginName, $output);
                $this->updatePlugin($pluginName, $output);
            }
        }
    }
}
