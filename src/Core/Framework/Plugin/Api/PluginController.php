<?php declare(strict_types=1);

namespace TeamDeployment\Plugin\Deployment\Core\Framework\Plugin\Api;

use Composer\IO\NullIO;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Plugin\Exception\PluginComposerJsonInvalidException;
use Shopware\Core\Framework\Plugin\PluginLifecycleService;
use Shopware\Core\Framework\Plugin\PluginService;
use Shopware\Core\Framework\Plugin\Requirement\Exception\RequirementStackException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Amir El Sayed <a.elsayed@basecom.de>
 */
class PluginController extends AbstractController
{
    /**
     * @var PluginService
     */
    private $pluginService;

    /**
     * @var PluginLifecycleService
     */
    private $pluginLifecycleService;

    /**
     * @var EntityRepositoryInterface
     */
    private $pluginRepo;

    public function __construct(
        PluginService $pluginService,
        PluginLifecycleService $pluginLifecycleService,
        EntityRepositoryInterface $pluginRepo
    ) {
        $this->pluginService = $pluginService;
        $this->pluginLifecycleService = $pluginLifecycleService;
        $this->pluginRepo = $pluginRepo;
    }

    /**
     * @Route("/api/v{version}/_action/plugin/deploy", name="api.action.plugin.installAll", methods={"POST"})
     * @param Context $context
     *
     * @return JsonResponse
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     * @throws \Shopware\Core\Framework\Plugin\Exception\PluginComposerJsonInvalidException
     * @throws \Shopware\Core\Framework\Plugin\Exception\PluginNotInstalledException
     * @throws \Shopware\Core\Framework\Plugin\Requirement\Exception\RequirementStackException
     */
    public function deployPlugins(Context $context): JsonResponse
    {
        $pluginList = $this->refreshPlugins($context);

        foreach($pluginList as $plugin) {
            $this->pluginLifecycleService->updatePlugin($plugin, $context);
            $this->pluginLifecycleService->installPlugin($plugin, $context);
            $this->pluginLifecycleService->activatePlugin($plugin, $context);
        }

        return new JsonResponse($pluginList);
    }

    /**
     * @Route("/api/v{version}/_action/plugin/install", name="api.action.plugin.install_all", methods={"POST"})
     * @param Context $context
     *
     * @return JsonResponse
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     * @throws \Shopware\Core\Framework\Plugin\Exception\PluginComposerJsonInvalidException
     * @throws \Shopware\Core\Framework\Plugin\Requirement\Exception\RequirementStackException
     */
    public function installPlugins(Context $context): JsonResponse
    {
        $pluginList = $this->refreshPlugins($context);

        foreach($pluginList as $plugin) {
            $this->pluginLifecycleService->installPlugin($plugin, $context);
        }

        return new JsonResponse($pluginList);
    }

    /**
     * @Route("/api/v{version}/_action/plugin/activate", name="api.action.plugin.activate_all", methods={"POST"})
     * @param Context $context
     *
     * @return JsonResponse
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     * @throws \Shopware\Core\Framework\Plugin\Exception\PluginNotInstalledException
     */
    public function activatePlugins(Context $context): JsonResponse
    {
        $pluginList = $this->refreshPlugins($context);

        foreach($pluginList as $plugin) {
            $this->pluginLifecycleService->activatePlugin($plugin, $context);
        }

        return new JsonResponse($pluginList);
    }

    /**
     * @Route("/api/v{version}/_action/plugin/update", name="api.action.plugin.update_all", methods={"POST"})
     * @param Context $context
     *
     * @return JsonResponse
     * @throws PluginComposerJsonInvalidException
     * @throws RequirementStackException
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    public function updatePlugins(Context $context): JsonResponse
    {
        $pluginList = $this->refreshPlugins($context);

        foreach($pluginList as $plugin) {
            $this->pluginLifecycleService->updatePlugin($plugin, $context);
        }

        return new JsonResponse($pluginList);
    }

    /**
     * @Route("/api/v{version}/_action/plugin/deactivate", name="api.action.plugin.deactivate_all", methods={"POST"})
     * @param Context $context
     *
     * @return JsonResponse
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     * @throws \Shopware\Core\Framework\Plugin\Exception\PluginNotActivatedException
     * @throws \Shopware\Core\Framework\Plugin\Exception\PluginNotInstalledException
     */
    public function deactivatePlugins(Context $context): JsonResponse
    {
        $pluginList = $this->refreshPlugins($context);

        foreach($pluginList as $plugin) {
            $this->pluginLifecycleService->deactivatePlugin($plugin, $context);
        }

        return new JsonResponse($pluginList);
    }

    /**
     * @param $context
     *
     * @return EntitySearchResult
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    private function refreshPlugins($context) : EntitySearchResult {
        $this->pluginService->refreshPlugins($context, new NullIO());

        $pluginList = $this->pluginRepo->search(new Criteria([]), $context);

        return $pluginList;
    }
}