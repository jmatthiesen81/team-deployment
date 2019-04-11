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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class Deployment
 *
 * @package TeamDeployment\Plugin\Deployment\Commands
 */
class Deployment extends Command
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $pluginRepository;

    /**
     * Deployment constructor.
     *
     * @param string                    $name
     * @param EntityRepositoryInterface $pluginRepository
     */
    public function __construct(string $name, EntityRepositoryInterface $pluginRepository)
    {
        parent::__construct($name);
        $this->pluginRepository = $pluginRepository;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Refreshing plugins...');
        $command = $this->getApplication()->find('plugin:refresh');
        $command->run(new ArrayInput([]), $output);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('managedByComposer', 1));
        $plugins = $this->pluginRepository->search($criteria, Context::createDefaultContext());

        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            $pluginName = $plugin->getName();
            $helper     = $this->getHelper('question');

            $question   = new ConfirmationQuestion('Install and activate ' . $pluginName . ' ?(Y/n) ', true, '/^(y|j)/i');
            if ($helper->ask($input, $output, $question)) {
                $arguments = new ArrayInput(['plugins' => [$pluginName], '--activate' => true]);
                $command   = $this->getApplication()->find('plugin:install');
                $command->run($arguments, $output);
            }

            $question = new ConfirmationQuestion('Update ' . $pluginName . '? (Y/n) ', true, '/^(y|j)/i');
            if ($helper->ask($input, $output, $question)) {
                $arguments = new ArrayInput(['plugins' => [$pluginName]]);
                $command   = $this->getApplication()->find('plugin:update');
                $command->run($arguments, $output);
            }
        }
    }
}