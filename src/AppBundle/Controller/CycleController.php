<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Cycle;
use AppBundle\Form\CycleType;

/**
 * Cycle controller.
 *
 */
class CycleController extends Controller
{

    /**
     * Lists all Cycle entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Cycle')->findAll();

        return $this->render('AppBundle:Cycle:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Cycle entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity  = new Cycle();
        $form = $this->createForm(new CycleType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_cycle_show', array('id' => $entity->getId())));
        }

        return $this->render('AppBundle:Cycle:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Cycle entity.
     *
     */
    public function newAction()
    {
        $entity = new Cycle();
        $form   = $this->createForm(new CycleType(), $entity);

        return $this->render('AppBundle:Cycle:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Cycle entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Cycle')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Cycle:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to edit an existing Cycle entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Cycle')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $editForm = $this->createForm(new CycleType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Cycle:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Cycle entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Cycle')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new CycleType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_cycle_edit', array('id' => $id)));
        }

        return $this->render('AppBundle:Cycle:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Cycle entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Cycle')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Cycle entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_cycle'));
    }

    /**
     * Creates a form to delete a Cycle entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
