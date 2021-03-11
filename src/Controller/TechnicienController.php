<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;


use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TacheRepository;
use App\Repository\UserRepository;
use App\Repository\MaterielRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\Tache;
use App\Entity\User;


/**
* @Route("/technicien", name="technicien_")
*/
class TechnicienController extends AbstractController
{
    /**
     * @Route("/", name="accueil")
     */
    public function index(TacheRepository $repo): Response
    {
        
        $id = $this->get('security.token_storage')->getToken()->getUser()->getId();
        $tech = $this->get('security.token_storage')->getToken()->getUser();
        /*$tachesAttribue = $repo->findBy(
            array('Etat' => 'Attribuée')
        );
        $countAttribue = 0;
        foreach($tachesAttribue as $tache){
            foreach($tache->getTechnicien() as $tech ){
                if($id == $tech->getId()){
                    $countAttribue++;
                }
            }
        }*/
        $countAttribue = 0;
        foreach($tech->getTaches() as $tache){
            if($tache->getEtat() == "Attribuée"){
                $countAttribue++;
            }
        }
        /*$tachesEncours = $repo->findBy(
            array('Etat' => 'En cours..')
            
        );
        foreach($tachesEncours as $tache){
            foreach($tache->getTechnicien() as $tech ){
                if($id == $tech->getId()){
                    $countEnCours++;
                }
            }
        }*/
        $countEnCours = 0;
        foreach($tech->getTaches() as $tache){
            if($tache->getEtat() == "En cours.."){
                $countEnCours++;
            }
        }
        return $this->render('technicien/index.html.twig', [
            'controller_name' => 'TechnicienController',
            'nbtacheAttribue' => $countAttribue,
            'nbtacheEnCours' => $countEnCours
        ]);
    }

    /**
     * @Route("/profil/{id}", name="profil")
     */
    public function profil(User $user, Request $request)
    {
        $form = $this->createFormBuilder($user)
        ->add('email', EmailType::class,[
            'constraints' => [
                new NotBlank([
                    'message' => 'Merci d\'entrer un e-mail',
                ]),
            ],
            'required' => true,
            'attr' => ['class' =>'form-control'],
        ])
        ->add('nomComplet')
        ->add('image',FileType::class,[
            'mapped'=>false
        ])
        ->add('telephone')
        
        ->add('valider', SubmitType::class)
        
        ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $file = $form->get('image')->getData();
            $fileName= md5(uniqid()).'.'.$file->guessExtension();
            $file->move(
                $this->getParameter('images_directory'),
                $fileName
            );
            $user->setImage($fileName);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('message', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('technicien_accueil');
        }
        
        return $this->render('technicien/profile.html.twig', [
            'userForm' => $form->createView()
        ]);
    }


    /**
     * @Route("/AffectedTasks", name="AffectedTasks")
     */
    public function AffectedTasks(TacheRepository $repo , MaterielRepository $MaterielRepository ): Response
    {
        $currentTech = $this->get('security.token_storage')->getToken()->getUser();
        
        $tachesAffecte = $currentTech->getTaches();
        

        /*$materiels = $MaterielRepository->findBy(
            ['disponible' => 0]
        );*/
        return $this->render('technicien/AffectedTasks.html.twig', [
            'taches'=> $tachesAffecte,
            'currentDate' => new \DateTime ,
            
            'currentTech' => $currentTech,
            'tch' => $repo->findAll()
        ]);
    }

    /**
     * @Route("/ProgresTask/{id}", name="progres_edit")
     */
    public function editProgres(Tache $tache , $id , Request $request , TacheRepository $repo ,EntityManagerInterface $manager , MaterielRepository $MaterielRepository ): Response
    {
        $form = $this->createFormBuilder($tache)
        ->add('progress', RangeType::class, [
            'attr' => [
                'min' => 0,
                'max' => 100
            ]
        ])
        ->getForm();

        $form->handleRequest($request);

        /*$materiels = $MaterielRepository->findBy(
            ['tache' => $id]
        );*/
 
         if ($form->isSubmitted() && $form->isValid()){ 
            $tache->setEtat("En cours..");
            $manager->persist($tache);
             $manager->flush();

             if($tache->getProgress() == 100){
                
                foreach ($tache->getMateriel() as  $materiel) {
                    $tache->removeMateriel($materiel);
                }
                
                foreach ($tache->getTechnicien() as $technicien) {
                    $tache->removeTechnicien($technicien);
                }

                
                $tache->setEtat("Finie");
                
                $manager->persist($tache);
                $manager->flush();

             }
 
             return $this->redirectToRoute('technicien_AffectedTasks');
         }
        return $this->render('technicien/editProgres.html.twig', [
        
            'formTache' => $form->createView() 
            //'materiels' => $materiels
        ]);
    }

    /**
     * @Route("/MaterielsTask/{id}", name="materiels_finished")
     */
    public function editMateriels(Tache $tache, $id , Request $request , TacheRepository $repo ,EntityManagerInterface $manager , MaterielRepository $MaterielRepository ): Response
    {
        $materiels = $tache->getMateriel();
        return $this->render('technicien/editFinishedMateriel.html.twig', [
        
            'materiels' => $materiels,
            'tache'=> $tache
        ]);
    }

    /**
     * @Route("/vidermateriel/{idtch}/{id}", name="materiel_vider")
     */
    public function viderMateriel( $id , $idtch, MaterielRepository $repo, TacheRepository $repoTache,EntityManagerInterface $manager){
        $tache = $repoTache->find($idtch);
        $materiel = $repo->find($id);
        $tache->removeMateriel($materiel);
        //$materiel->setTache(null);
        //$materiel->setDisponible(true);
        $manager->flush();

        return $this->redirectToRoute('technicien_AffectedTasks');
    }
}
