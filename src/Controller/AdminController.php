<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditUserType;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use App\Repository\TacheRepository;


/**
 * @Route("/admin", name="admin_")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="accueil")
     */
    public function index(TacheRepository $repo , UserRepository $repoUsers): Response
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
        $users = $repoUsers->findAll();
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'nbtacheAttribue' => sizeof($tachesAttribue),
            'nbtacheEnCours' => sizeof($tachesEncours) ,
            'nbtacheFinie' => sizeof($tachesFinie),
            'nbtacheNonAttribue' => sizeof($tachesNonAttribue),
            'nbUsers' => sizeof($users)
        ]);
    }

    /**
     * @Route("/utilisateurs", name="utilisateurs")
     */
    public function usersList(UserRepository $users)
    {
        return $this->render('admin/users.html.twig', [
            'users' => $users->findAll(),
        ]);
    }

    /**
     * @Route("/AjouterUser", name="add_user")
     */
    public function registration(Request $request , EntityManagerInterface $manager,
    UserPasswordEncoderInterface $encoder){
        $user = new User();

        $form = $this->createForm(RegistrationType::class ,$user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $hash = $encoder->encodePassword($user , $user->getPassword());
            $user->setPassword($hash);
            $file = $form->get('image')->getData();
        $fileName= md5(uniqid()).'.'.$file->guessExtension();
        try {
            $file->move(
                $this->getParameter('images_directory'),
                $fileName
            );
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }
            $user->setImage($fileName);
            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute('admin_utilisateurs');
        }

        return $this->render('admin/adduser.html.twig',[
            'form'=>$form->createView()
        ]);
    }

    /**
     * @Route("/utilisateurs/modifier/{id}", name="modifier_utilisateur")
     */
    public function editUser(User $user, Request $request)
    {
        $form = $this->createForm(EditUserType::class, $user);
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
            return $this->redirectToRoute('admin_utilisateurs');
        }
        
        return $this->render('admin/edituser.html.twig', [
            'userForm' => $form->createView(),
            'user' => $user
        ]);
    }


    /**
     * @Route("/utilisateurs/supprimer/{id}", name="supprimer_utilisateur")
     */
    public function deleteUser($id ,UserRepository $repo, EntityManagerInterface $manager)
    {
        $user = $repo->find($id);

        $manager->remove($user);
        $manager->flush();

        return $this->redirectToRoute('admin_utilisateurs');
        
        
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
            return $this->redirectToRoute('admin_accueil');
        }
        
        return $this->render('admin/profile.html.twig', [
            'userForm' => $form->createView()
        ]);
    }
}
