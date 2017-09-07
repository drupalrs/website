<?php

namespace Drupal\breadcrumb\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Url;

class BreadcrumbBuilder implements BreadcrumbBuilderInterface{

  /**
  * {@inheritdoc}
  */
  public function applies(RouteMatchInterface $attributes) {
    $parameters = $attributes->getParameters()->all();
    if (!empty($parameters['node'])) {
      if (in_array($parameters['node']->getType(), ['page', 'blog','poslovi','dogadaji','kompanija'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
  * {@inheritdoc}
  */
  public function build(RouteMatchInterface $route_match) {
    $request = \Drupal::request();

    $type = \Drupal::routeMatch()->getParameter('node')->getType();

    $title = '';
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $title = \Drupal::service('title_resolver')->getTitle($request, $route);
    }

    $breadcrumbs = new Breadcrumb();

    // Create Breadcrumb links.
    $breadcrumbs->addLink(Link::createFromRoute('Početna strana', '<front>'));
    //$breadcrumbs->addLink(Link::createFromRoute('Basic page', '<front>'));
    //$breadcrumbs->addLink(Link::createFromRoute('Add node', 'node.add_page'));
    //$breadcrumbs->addLink(Link::createFromRoute('Edit node', 'entity.node.edit_form', ['node' => 1]));
    //$breadcrumbs->addLink(Link::fromTextAndUrl($title, Url::fromUri('internal:/blog')));
    if($type == 'blog')
      $breadcrumbs->addLink(Link::fromTextAndUrl('Blog', Url::fromUri('internal:/blog')));

    if($type == 'poslovi')
      $breadcrumbs->addLink(Link::fromTextAndUrl('Poslovi', Url::fromUri('internal:/poslovi')));

    if($type == 'dogadaji')
      $breadcrumbs->addLink(Link::fromTextAndUrl('Događaji', Url::fromUri('internal:/dogadjaji')));

    if($type == 'kompanija')
      $breadcrumbs->addLink(Link::fromTextAndUrl('Kompanije', Url::fromUri('internal:/kompanije')));

    $breadcrumbs->addLink(Link::createFromRoute($title, '<none>'));

    $breadcrumbs->addCacheContexts(["url"]);

    return $breadcrumbs;
  }

}
