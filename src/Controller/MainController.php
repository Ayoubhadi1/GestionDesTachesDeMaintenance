<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



class MainController extends AbstractController
{
    

    /**
     * @Route("/", name="main")
     */
    public function index(): Response
    {
        $role = $this->get('security.token_storage')->getToken()->getUser()->getRoles();
        if($role == ["ROLE_ADMIN"]){
            return $this->redirectToRoute('admin_accueil');
        }
        elseif ($role == ["ROLE_RESPO"]) {
            return $this->redirectToRoute('responsable_accueil');
        }
        elseif ($role == ["ROLE_TECH"]) {
            return $this->redirectToRoute('technicien_accueil');
        }
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController'
        ]);
    }

    /**
     * @Route("/Home", name="home")
     */
    public function home(): Response
    {
        
        return $this->render('main/home.html.twig', [
            'controller_name' => 'MainController'
        ]);
    }
}
