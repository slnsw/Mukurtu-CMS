<?php

namespace Drupal\mukurtu_multipage_items\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\mukurtu_multipage_items\MultipageItemInterface;
use Drupal\mukurtu_multipage_items\Entity\MultipageItem;

/**
 * Returns responses for Mukurtu Multipage Items routes.
 */
class MultipageItemPageController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('config.factory'),
    );
  }

  public function viewRedirect(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    $config = $this->configFactory->get('mukurtu_multipage_items.settings');
    $controllerString = $config->get('_controller');
    list($controllerClass, $controllerMethod) = explode('::', $controllerString, 2);
    $originalController = $controllerClass::create(\Drupal::getContainer());

    $mpi = $this->getMultipageEntity($node);

    // This conditional looks like a mistake but be careful,
    // it's intentionally crafted to avoid a redirect loop with ::view.
    if ($mpi && $mpi->access('view') && $this->viewAccess($this->currentUser(), $node)->isAllowed()) {
      // Redirect to the multipage view.
      $url = Url::fromRoute('mukurtu_multipage_items.multipage_node_view', ['node' => $node->id()]);
      return new RedirectResponse($url->toString());
    }

    // Fail back to original view controller.
    return $originalController->{$controllerMethod}($node, $view_mode, $langcode);
  }

  public function viewFirstPage(MultipageItemInterface $mpi) {
    $firstPage = $mpi->getFirstPage();
    if ($firstPage) {
      $url = Url::fromRoute('mukurtu_multipage_items.multipage_node_view', ['node' => $firstPage->id()]);
      return new RedirectResponse($url->toString());
    }
    return [];
  }

  public function viewFirstPageAccess(AccountInterface $account, MultipageItemInterface $mpi) {
    return $mpi->access('view', $account, TRUE);
  }

  public function newFromNode(NodeInterface $node) {
    $build = [];
    $mpi = MultipageItem::create(['title' => $node->getTitle(), 'field_pages' => [$node->id()]]);
    $form = $this->entityTypeManager()
      ->getFormObject('multipage_item', 'add')
      ->setEntity($mpi);
    $build[] = $this->formBuilder()->getForm($form);

    return $build;
  }


  public function newFromNodeAccess(AccountInterface $account, NodeInterface $node) {
    $access_handler = $this->entityTypeManager()->getAccessControlHandler('multipage_item');
    // User must be able to create MPIs.
    if (!$access_handler->createAccess()) {
      return AccessResult::forbidden();
    }

    // Node cannot be in an existing MPI.
    if($this->getMultipageEntity($node)) {
      return AccessResult::forbidden();
    }

    // User must have edit access to the item as well.
    return $node->access('update', $account, TRUE);
  }

  protected function getMultipageEntity($node) {
    $query = $this->entityTypeManager()->getStorage('multipage_item')->getQuery();
    $result = $query->condition('field_pages', $node->id())
      ->accessCheck(TRUE)
      ->execute();
    if (count($result) == 1) {
      $mpiId = reset($result);
      return $this->entityTypeManager()->getStorage('multipage_item')->load($mpiId);
    }
    return NULL;
  }

  public function getSelectedPageAjax(NodeInterface $page) {
    /** @var \Drupal\mukurtu_multipage_items\MultipageItemInterface $multipageitem */
    $multipageitem = $this->getMultipageEntity($page);
    $response = new AjaxResponse();
    if ($multipageitem && $multipageitem->hasPage($page) && $page->access('view')) {
      $view_builder = $this->entityTypeManager()->getViewBuilder('node');
      $content['mukurtu_multipage_item_selected_page'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'mukurtu-multipage-item-selected-page',
        ],
      ];

      $content['mukurtu_multipage_item_selected_page']['teaser'] = $view_builder->view($page, 'full');
      $response->addCommand(new ReplaceCommand('#mukurtu-multipage-item-selected-page', $content));
    }
    return $response;
  }

  /**
   * Custom access for view route.
   *
   * @param AccountInterface $account
   *   The account to check access for.
   * @param NodeInterface $node
   *   The page node to view.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function viewAccess(AccountInterface $account, NodeInterface $node) {
    // Only checking for access to the page. We want to keep the MPI URL
    // durable (e.g., /node/{node}/multipage) even if the MPI gets deleted.
    // If a user has access to the page but not the MPI we will redirect them
    // in the view method to the node's canonical route.
    return $node->access('view', $account, TRUE);
  }

  /**
   * Custom access for edit route.
   *
   * @param AccountInterface $account
   *   The account to check access for.
   * @param NodeInterface $node
   *   The page node to of the multipage item to edit.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function editAccess(AccountInterface $account, NodeInterface $node) {
    $mpi = $this->getMultipageEntity($node);
    if ($mpi) {
      return $mpi->access('edit', $account, TRUE);
    }
    return AccessResult::forbidden();
  }


  /**
   * Builds the view page.
   */
  public function view(NodeInterface $node) {
    $mpi = $this->getMultipageEntity($node);
    $view_builder = $this->entityTypeManager()->getViewBuilder('node');

    // Redirect back to the single node if user can't see the MPI.
    if (!$mpi || !$mpi->access('view')) {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
      return new RedirectResponse($url->toString());
    }

    /** @var \Drupal\mukurtu_multipage_items\Entity\MultipageItem $mpi */
    $current_page = $node ?? $mpi->getFirstPage();
    $pages = $mpi->getPages(TRUE);
    $toc_options = array_map(fn($p) => $p->getTitle(), $pages);
    $toc = [
      '#id' => 'multipage-item-table-of-contents',
      '#type' => 'select',
      '#title' => $this->t('Jump to page'),
      '#options' => $toc_options,
      '#value' => $current_page->id(),
    ];

    return [
      '#theme' => 'multipage_item_book_view',
      '#pages' => array_map(fn($p) => $view_builder->view($p, 'content_browser'), $pages),
      '#page_nav_attributes' => NULL,
      '#table_of_contents' => $toc,
      '#current_page' => $view_builder->view($current_page, 'full'),
      '#attached' => [
        'library' => [
          'mukurtu_multipage_items/multipage-view-nav',
          'mukurtu_multipage_items/splide',
        ],
      ],
    ];
  }

  /**
   * Builds the edit page.
   */
  public function edit(NodeInterface $node) {
    $mpi = $this->getMultipageEntity($node);
    if ($mpi) {
      $url = Url::fromRoute('entity.multipage_item.edit_form', ['multipage_item' => $mpi->id()]);
      return new RedirectResponse($url->toString());
    }
  }

}