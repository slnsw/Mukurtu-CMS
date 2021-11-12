<?php

namespace Drupal\mukurtu_protocol;

//use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
//use Drupal\node\Entity\Node;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;

/**
 * Provides a service for managing memberships in Communities and Protocols.
 */
class MukurtuMembershipManager {

  /**
   * Load the protocol lookup table.
   */
  public function __construct() {
  }

  /**
   * Add a user to a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group node.
   * @param Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function addMember(EntityInterface $group, AccountInterface $account) {
    // Is the account already a member of the group?
    $membership = Og::getMembership($group, $account, OgMembershipInterface::ALL_STATES);
    if (!$membership) {
      $membership = Og::createMembership($group, $account);
      $membership->save();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Remove a user from a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group node.
   * @param Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function removeMember(EntityInterface $group, AccountInterface $account) {
    $membership = Og::getMembership($group, $account, OgMembershipInterface::ALL_STATES);
    if ($membership) {
      $membership->delete();
      return TRUE;
    }

    return FALSE;
  }

  public function setRoles(EntityInterface $group, AccountInterface $account, array $roles) {
    $nonMemberRoleId = "{$group->getEntityTypeId()}-{$group->bundle()}-non-member";
    $memberRoleId = "{$group->getEntityTypeId()}-{$group->bundle()}-member";

    $roleManager = \Drupal::service('og.role_manager');
    $allProtocolRoles = $roleManager->getRolesByBundle($group->getEntityTypeId(), $group->bundle());

    // Build a list of enabled roles.
    $enabledRoles = [];
    foreach ($roles as $role) {
      if (!in_array($role, [$nonMemberRoleId, $memberRoleId])) {
        if (isset($allProtocolRoles[$role])) {
          $enabledRoles[] = $allProtocolRoles[$role];
        }
      }
    }

    if (in_array($nonMemberRoleId, $roles)) {
      $this->removeMember($group, $account);
      return;
    }

    if (in_array($memberRoleId, $roles)) {
      $this->addMember($group, $account);
      $membership = Og::getMembership($group, $account, OgMembershipInterface::ALL_STATES);
      $membership->setRoles($enabledRoles);
    }
  }

  public function removeRoles(EntityInterface $group, AccountInterface $account, $roles) {
    $membership = Og::getMembership($group, $account, OgMembershipInterface::ALL_STATES);
    if ($membership) {
      foreach ($roles as $role) {
        $membership->revokeRoleById($role);
      }
      $membership->save();
      return TRUE;
    }

    return FALSE;
  }

}
