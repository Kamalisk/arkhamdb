<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Controller\RegistrationController as BaseController;
use Symfony\Component\HttpFoundation\Request;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RegistrationController extends BaseController
{
    private $eventDispatcher;
    private $formFactory;
    private $userManager;
    private $tokenStorage;

    public function __construct(EventDispatcherInterface $eventDispatcher, FactoryInterface $formFactory, UserManagerInterface $userManager, TokenStorageInterface $tokenStorage)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
        $this->tokenStorage = $tokenStorage;
        parent::__construct($eventDispatcher, $formFactory, $userManager, $tokenStorage);
    }
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

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function registerAction(Request $request)
    {
        $user = $this->userManager->createUser();
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $this->formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        // hack to stop weird usernames for now
        if (isset($request->request->get('fos_user_registration_form')['username'])) {
            if (preg_match('/[ |\\\\\/:@<>]/m', $request->request->get('fos_user_registration_form')['username'])) {
                return new RedirectResponse($this->generateUrl('fos_user_registration_register'));
            }
        }

        // check recaptcha url
        // https://www.google.com/recaptcha/api/siteverify
        if ($request->request->get('g-recaptcha-response')) {
            $token = $request->request->get('g-recaptcha-response');

            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = ['secret' => $this->container->getParameter('captcha'), 'response' => $token];

            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data),
                ],
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            if ($result) {
                $data = json_decode($result);
                if ($data && $data->success) {
                    // if we got here then its all good
                } else {
                    return new RedirectResponse($this->generateUrl('fos_user_registration_register'));
                }
            } else {
                return new RedirectResponse($this->generateUrl('fos_user_registration_register'));
            }


        } else {
            return $this->render('@FOSUser/Registration/register.html.twig', array(
                'form' => $form->createView(),
            ));
        }


        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $event = new FormEvent($form, $request);
                $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

                $this->userManager->updateUser($user);

                if (null === $response = $event->getResponse()) {
                    $url = $this->generateUrl('fos_user_registration_confirmed');
                    $response = new RedirectResponse($url);
                }

                $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

                return $response;
            }

            $event = new FormEvent($form, $request);
            $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_FAILURE, $event);

            if (null !== $response = $event->getResponse()) {
                return $response;
            }
        }

        return $this->render('@FOSUser/Registration/register.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}