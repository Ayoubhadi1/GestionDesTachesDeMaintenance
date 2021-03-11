<?php

namespace App\Controller;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TacheRepository;
use App\Repository\UserRepository;
use App\Repository\MaterielRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type as Form;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Form\TaskType;
use App\Entity\Tache;
use App\Entity\User;
use App\Entity\Materiel;
use Doctrine\ORM\PersistentCollection;


/**
* @Route("/responsable", name="responsable_")
*/
class ResponsableController extends AbstractController
{
    /**
     * @Route("/", name="accueil")
     */
    public function index(TacheRepository $repo): Response
    {
        $tachesAttribue = $repo->findBy(
            array('Etat' => 'Attribuée')
            
        );
        $tachesEncours = $repo->findBy(
            array('Etat' => 'En cours..')
            
        );
        $tachesFinie = $repo->findBy(
            array('Etat' => 'Finie')
            
        );
        $tachesNonAttribue = $repo->findBy(
            array('Etat' => 'Pas encore attribuée')
            
        );
        return $this->render('responsable/index.html.twig', [
            'controller_name' => 'ResponsableController',
            'nbtacheAttribue' => sizeof($tachesAttribue),
            'nbtacheEnCours' => sizeof($tachesEncours) ,
            'nbtacheFinie' => sizeof($tachesFinie),
            'nbtacheNonAttribue' => sizeof($tachesNonAttribue)
        ]);
    }



    /**
     * @Route("/ganttRespo", name="gantt_respo")
     */
    public function afficherGantt(): Response
    {
        
        return $this->render('responsable/ganttRespo.html.twig');
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
            return $this->redirectToRoute('responsable_accueil');
        }
        
        return $this->render('responsable/profile.html.twig', [
            'userForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/AllTasks", name="AllTasks")
     */
    public function AllTasks(TacheRepository $repo , MaterielRepository $MaterielRepository ): Response
    {
        /*$materiels = $MaterielRepository->findBy(
            ['disponible' => 0]
        );*/
        return $this->render('responsable/AllTasks.html.twig', [
            'taches'=> $repo->findAll(),
            'currentDate' => new \DateTime ,
            
            't'=> $MaterielRepository->findAll()
        ]);
    }

    /**
     * @Route("/AllMateriels", name="AllMateriels")
     */
    public function AllMateriels(MaterielRepository $repo): Response
    {
        return $this->render('responsable/AllMateriels.html.twig', [
            'materiels'=> $repo->findAll()
        ]);
    }

    /**
     * @Route("/task/new",name="task_create")
     */
    public function ajouterTache(Tache $tache= null, Request $request , EntityManagerInterface $manager){
        
        if(!$tache){
            $tache = new Tache();
        }
        
        $form = $this->createForm(TaskType::class ,$tache);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            if(!$tache->getId()){
                $responsable = $this->get('security.token_storage')->getToken()->getUser();
                $tache->setDateDebut(new \DateTime);
                $tache->setEtat("Pas encore attribuée");
                $tache->setResponsable($responsable);
                $tache->setProgress(0);
            }
          

            $manager->persist($tache);
            $manager->flush();

            return $this->redirectToRoute('responsable_AllTasks');
        }

        return $this->render('responsable/CreateTask.html.twig',[
            'formTache' => $form->createView() 
            
        ]);
    }

    /**
     * @Route("/tache/{id}/AssignerTech" , name="AssignerTech_edit")
     */
    public function assignerTech(Tache $tache= null, Request $request , EntityManagerInterface $manager ,MaterielRepository $MaterielRepository){
        
        if(!$tache){
             $tache = new Tache();
         }
         
         //$form = $this->createForm(TicketType::class ,$ticket);
         $form = $this->createFormBuilder($tache)
         ->add('titre')
         ->add('description',TextareaType::class)
         ->add('dateFin', DateType::class)
         ->add('technicien', EntityType::class,[
             'class'=>User::class,
             'query_builder' => function (UserRepository $er) {
                return $er->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '["ROLE_TECH"]');
                },
             'choice_label'=> 'nom_complet',
             'multiple' => true,
             'constraints' => [
                
                new Assert\Callback([
                    // Ici $value prend la valeur du champs que l'on est en train de valider,
                    // ainsi, pour un champs de type TextType, elle sera de type string.
                    'callback' => static function (?PersistentCollection $value , ExecutionContextInterface $context) {
                        if (!$value) {
                            return;
                        }
                        $indispo = array();
                        foreach($value as $tech){
                           
                            if(! ($tech->estDisponible())){
                                array_push($indispo, $tech->getNomComplet());
                            }
                        }
                        $afficheTech = "";
                        foreach ($indispo as $value) {
                            $afficheTech = $afficheTech . $value.' , ' ;
                        };

                        if(sizeof($indispo) != 0){
                            if(sizeof($indispo) == 1 ){
                                $context
                                ->buildViolation('Le technicien '.$afficheTech.' est indisponible .')
                                ->atPath('[technicien]')
                                ->addViolation()
                            ;
                            }else{
                                $context
                                ->buildViolation('Les techniciens '.$afficheTech.' sont indisponibles .')
                                ->atPath('[technicien]')
                                ->addViolation()
                            ;
                            }
                        }
    
                        
                    },
                ]),
            ]
          ])
          
         ->getForm();

        /* $materiels = $MaterielRepository->findBy(
            ['disponible' => 1]
        );*/
        
 
         $form->handleRequest($request);
 
         if ($form->isSubmitted() && $form->isValid()){

             $tache->setEtat("Attribuée");
             $tache->setProgress(0);
             $manager->persist($tache);
             $manager->flush();
 
             return $this->redirectToRoute('responsable_AllTasks');
             //Mailing (envoyer mail ou sms au technicien - Notification)

            }
            
         return $this->render('responsable/AssignerTech.html.twig',[
             'formTache' => $form->createView() 
         ]);
     }

      /**
     * @Route("/tache/{id}/solliciterMateriel" , name="solliciter_materiel")
     */
    public function solliciterMateriel(Tache $tache= null, Request $request , EntityManagerInterface $manager ,MaterielRepository $MaterielRepository , TacheRepository $TacheRepo){
        
        if(!$tache){
             $tache = new Tache();
         }
         
         //$form = $this->createForm(TicketType::class ,$ticket);
         $form = $this->createFormBuilder($tache)
         ->add('titre')
         ->add('description',TextareaType::class)
         ->add('dateFin', DateType::class)
         ->add('materiel', EntityType::class,[
             'class'=>Materiel::class,
             /*'query_builder' => function (UserRepository $er) {
                return $er->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '["ROLE_TECH"]');
                },*/
             'choice_label'=> 'nom',
             'multiple' => true,
             'constraints' => [
                
                new Assert\Callback([
                    // Ici $value prend la valeur du champs que l'on est en train de valider,
                    // ainsi, pour un champs de type TextType, elle sera de type string.
                    'callback' => static function (?PersistentCollection $value , ExecutionContextInterface $context) {
                        if (!$value) {
                            return;
                        }
                        $indispo = array();
                        foreach($value as $mat){
                           
                            if(! ($mat->estDisponible())){
                                array_push($indispo, $mat->getNom());
                            }
                        }
                        $afficheMat = "";
                        foreach ($indispo as $value) {
                            $afficheMat = $afficheMat . $value.' , ' ;
                        };

                        if(sizeof($indispo) != 0){
                            if(sizeof($indispo) == 1 ){
                                $context
                                ->buildViolation('Le matériel '.$afficheMat.' est indisponible .')
                                ->atPath('[materiel]')
                                ->addViolation()
                            ;
                            }else{
                                $context
                                ->buildViolation('Les matériels '.$afficheMat.' sont indisponibles .')
                                ->atPath('[materiel]')
                                ->addViolation()
                            ;
                            }
                        }
    
                        
                    },
                ]),
            ]
        ])
        
          
         ->getForm();

         /*$materiels = $MaterielRepository->findBy(
            ['disponible' => 1]
        );*/
        
        
         $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()){
            
            /*$materiels = $tache->getMateriel();
            foreach($materiels as $value){
            $value->setDisponible(false);
            }*/
             
             $manager->persist($tache);
             $manager->flush();
 
             return $this->redirectToRoute('responsable_AllTasks');
             //Mailing (envoyer mail ou sms au technicien - Notification)

        }
            
         return $this->render('responsable/SolliciterMaterel.html.twig',[
             'formTache' => $form->createView() 
             
         ]);
     }


     /**
     * @Route("/tache/edit/{id}" , name="tache_edit")
     */
    public function editTache(Tache $tache= null, Request $request , EntityManagerInterface $manager ,MaterielRepository $MaterielRepository){
        
        if(!$tache){
             $tache = new Tache();
         }
         
         //$form = $this->createForm(TicketType::class ,$ticket);
         $form = $this->createFormBuilder($tache)
         ->add('titre')
         ->add('description',TextareaType::class)
         ->add('dateFin', DateType::class)
         
          
         ->getForm();

        /* $materiels = $MaterielRepository->findBy(
            ['disponible' => 1]
        );*/
        
 
         $form->handleRequest($request);
 
         if ($form->isSubmitted() && $form->isValid()){

             
             $manager->persist($tache);
             $manager->flush();
 
             return $this->redirectToRoute('responsable_AllTasks');
            

            }
            
         return $this->render('responsable/editTache.html.twig',[
             'formTache' => $form->createView() 
         ]);
     }


     /**
     * @Route("/deletetask/{id}", name="task_delete")
     */
    public function supprimerTask( $id , TacheRepository $repo, EntityManagerInterface $manager){
        $tache = $repo->find($id);

        $manager->remove($tache);
        $manager->flush();

        return $this->redirectToRoute('responsable_AllTasks');
    }


     /**
     * @Route("/materiel/new",name="materiel_create")
     */
    public function ajouterMateriel(Materiel $materiel= null, Request $request , EntityManagerInterface $manager){
        
        if(!$materiel){
            $materiel = new Materiel();
        }
        
        $form = $this->createFormBuilder($materiel)
         ->add('nom')
         ->add('description',TextareaType::class)
         ->add('image',FileType::class,[
            'mapped'=>false
        ])
        
         
         ->getForm();

        $form->handleRequest($request);

        $currentUser = $this->get('security.token_storage')->getToken()->getUser();
        if ($form->isSubmitted() && $form->isValid()){

            if(!$materiel->getId()){
                
                
                $materiel->setCreator($currentUser);
                //$materiel->setDisponible(true);
                $file = $form->get('image')->getData();
                 $fileName= md5(uniqid()).'.'.$file->guessExtension();
                try {
                    $file->move($this->getParameter('images_directory'),$fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }
            $materiel->setImage($fileName);
            }
            

            $manager->persist($materiel);
            $manager->flush();

            return $this->redirectToRoute('responsable_AllMateriels');
        }

        return $this->render('responsable/CreateMateriel.html.twig',[
            'formMateriel' => $form->createView() ,
            'editMode'=> $materiel->getId() !== null
        ]);
    }

    
    /**
     * @Route("/materiel/edit/{id}" , name="Materiel_edit")
     */
    public function editMateriel(Materiel $materiel= null, Request $request , EntityManagerInterface $manager ,MaterielRepository $MaterielRepository){
        
        if(!$materiel){
             $materiel = new Materiel();
         }
         
        
         $form = $this->createFormBuilder($materiel)
         ->add('nom')
         ->add('description',TextareaType::class)
         ->add('image',FileType::class,[
            'mapped'=>false
        ])
        
         
         ->getForm();

        $form->handleRequest($request);

        /* $materiels = $MaterielRepository->findBy(
            ['disponible' => 1]
        );*/
        
 
         if ($form->isSubmitted() && $form->isValid()){


            $file = $form->get('image')->getData();
            $fileName= md5(uniqid()).'.'.$file->guessExtension();
            $file->move($this->getParameter('images_directory'),$fileName);
           $materiel->setImage($fileName);
             $manager->persist($materiel);
             $manager->flush();
 
             return $this->redirectToRoute('responsable_AllMateriels');
             

            }
            
         return $this->render('responsable/editMateriel.html.twig',[
             'formMateriel' => $form->createView() 
             
         ]);
     }



    /**
     * @Route("/materiel/{id}/AssignerMateriel" , name="AssignerMateriel_edit")
     */
    public function attribuerMateriel(Materiel $materiel= null, Request $request , EntityManagerInterface $manager ,MaterielRepository $MaterielRepository){
        
        if(!$materiel){
             $materiel = new Materiel();
         }
         
         
         $form = $this->createFormBuilder($materiel)
        ->add('tache', EntityType::class,[
            'class'=>Tache::class,
            'query_builder' => function (TacheRepository $er) {
                return $er->createQueryBuilder('t')
                ->where('t.Etat NOT LIKE :etat')
                ->setParameter('etat', 'Finie');
                },
            'choice_label'=> 'titre'
         ])
         
         ->getForm();

        $form->handleRequest($request);

         $materiels = $MaterielRepository->findBy(
            ['disponible' => 1]
        );
        
 
         if ($form->isSubmitted() && $form->isValid()){

            $materiel->setDisponible(false);

           
             $manager->persist($materiel);
             $manager->flush();
 
             return $this->redirectToRoute('responsable_AllMateriels');
             //Mailing (envoyer mail ou sms au technicien - Notification)

            }
            
         return $this->render('responsable/AssignerMateriel.html.twig',[
             'formMateriel' => $form->createView() ,
             'materiels' => $materiels
         ]);
     }

    /**
     * @Route("/deletemateriel/{id}", name="materiel_delete")
     */
    public function supprimerMateriel( $id , MaterielRepository $repo, EntityManagerInterface $manager){
        $materiel = $repo->find($id);

        $manager->remove($materiel);
        $manager->flush();

        return $this->redirectToRoute('responsable_AllMateriels');
    }
}
