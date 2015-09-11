<?php
namespace TGN\AdminAreaBundle\Controller;

use TGN\CoreBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use JMS\SecurityExtraBundle\Annotation as JMS;
use Umbrellaweb\Bundle\UsefulAnnotationsBundle\Annotation\CsrfProtector;
use TGN\CoreBundle\Entity\Citizen;
use TGN\CoreBundle\Form\CitizenChangeStatusType;

/**
 * @Route("/citizens")
 * @JMS\PreAuthorize("hasRole('ROLE_CITIZEN_MANAGER')")
 */
class CitizenController extends Controller
{

    /**
     * Citizens List
     * 
     * @Route("/", name="tgn_aa_citizen")
     * @Template()
     */
    public function indexAction()
    {
        /**
         * citizens - for grid
         * csrf - token for deletion
         * statuses - for filtering
         */
        return array(
            'citizens' => $this->getDoctrine()->getRepository('TGNCoreBundle:Citizen')->findAllJoinedToStatusArray(),
            'csrf' => $this->get('form.csrf_provider')->generateCsrfToken('delete-citizen'),
            'statuses' => $this->getDoctrine()->getRepository('TGNCoreBundle:Dictionary\AdminStatus')->findAll(),
        );
    }

    /**
     * Change citizen status
     *
     * @Route("/change-status/{id}",name="tgn_aa_citizen_change_status")
     * @ParamConverter("citizen", class="TGNCoreBundle:Citizen")
     * @Template("TGNAdminAreaBundle:Citizen:change-status.html.twig")
     */
    public function changeStatusAction(Request $request)
    {
        $citizen = $request->attributes->get('citizen');

        $form = $this->createForm(new CitizenChangeStatusType(), $citizen);

        if ($request->getMethod() == 'POST')
        {
            $form->bind($request);

            if ($form->isValid())
            {
                $em = $this->getDoctrine()->getEntityManager();
                $em->flush();

                $this->get('session')->setFlash('success', $this->get('translator')->trans('Citizen status <b>%citizen_name%</b> updated successfully.', array('%citizen_name%' => $citizen->getFullName())));

                return $this->redirect($this->generateUrl('tgn_aa_citizen'));
            }
        }

        return array(
            'form' => $form->createView(),
            'citizen' => $citizen
        );
    }

    /**
     * Delete citizen
     *
     * Route("/delete/{id}",name="tgn_aa_citizen_delete")
     * @Method("DELETE")
     * @ParamConverter("citizen", class="TGNCoreBundle:Citizen")
     * @CsrfProtector(intention="delete-citizen", name="_token")
     */
    public function deleteAction(Request $request)
    {
        $citizen = $request->attributes->get('citizen');
        $origin_name = $citizen->getFullName();

        try
        {
            //remove object from list
            $em = $this->getDoctrine()->getEntityManager();
            $em->remove($citizen);
            $em->flush();

            $this->get('session')->setFlash('success', $this->get('translator')->trans('Citizen %citizen_name% deleted successfully.', array('%citizen_name%' => $origin_name)));
        }
        catch (\Exception $e)
        {
            $this->get('session')->setFlash('error', $e->getMessage());
        }

        //redirect to citizens list
        return $this->redirect($this->generateUrl('tgn_aa_citizen'));
    }

}