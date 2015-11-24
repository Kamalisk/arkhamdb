<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Type;
use AppBundle\Form\TypeType;

/**
 * Type controller.
 *
 */
class TypeController extends Controller
{

    /**
     * Lists all Type entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Type')->findAll();

        return $this->render('AppBundle:Type:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Type entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Type();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_type_show', array('id' => $entity->getId())));
        }

        return $this->render('AppBundle:Type:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Type entity.
     *
     * @param Type $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Type $entity)
    {
        $form = $this->createForm(new TypeType(), $entity, array(
            'action' => $this->generateUrl('admin_type_create'),
            'method' => 'POST',
        ));

        return $form;
    }

    /**
     * Displays a form to create a new Type entity.
     *
     */
    public function newAction()
    {
        $entity = new Type();
        $form   = $this->createCreateForm($entity);

        return $this->render('AppBundle:Type:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Type entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Type')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Type:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Type entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Type')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AppBundle:Type:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Type entity.
    *
    * @param Type $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Type $entity)
    {
        $form = $this->createForm(new TypeType(), $entity, array(
            'action' => $this->generateUrl('admin_type_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Type entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Type')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('admin_type_edit', array('id' => $id)));
        }

        return $this->render('AppBundle:Type:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Type entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:Type')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Type entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_type'));
    }

    /**
     * Creates a form to delete a Type entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_type_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
