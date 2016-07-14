<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Controller\RegistrationController as BaseController;
use Symfony\Component\HttpFoundation\Request;

class RegistrationController extends BaseController
{
    /**
     * Receive the confirmation token from user email provider, login the user
     */
    public function confirmAction(Request $request, $token)
    {
        $userManager = $this->container->get('fos_user.user_manager');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            // User with token not found. Do whatever you want here
            return new RedirectResponse($this->container->get('router')->generate('fos_user_security_login'));
        }
        else{
            // Token found. Letting the FOSUserBundle's action handle the confirmation
            return parent::confirmAction($request, $token);
        }
    }
}