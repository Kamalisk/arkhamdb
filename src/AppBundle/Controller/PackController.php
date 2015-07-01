<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Pack;
use AppBundle\Form\PackType;

/**
 * Pack controller.
 *
 */
class PackController extends Controller
{

    /**
     * Lists all Pack entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Pack')->findAll();

        return $this->render('AppBundle:Pack:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Pack entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity  = new Pack();
        $form = $this->createForm(new PackType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_pack_show', array('id' => $entity->getId())));
        }

        return $this->render('AppBundle:Pack:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Pack entity.
     *
     */
    public function newAction()
    {
        $entity = new Pack();
        $form   = $this->createForm(new PackType(), $entity);

        return $this->render('AppBundle:Pack:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Pack entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Pack')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Pack:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to edit an existing Pack entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Pack')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }

        $editForm = $this->createForm(new PackType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Pack:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Pack entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Pack')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new PackType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_pack_edit', array('id' => $id)));
        }

        return $this->render('AppBundle:Pack:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Pack entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Pack')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Pack entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_pack'));
    }

    /**
     * Creates a form to delete a Pack entity by id.
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
