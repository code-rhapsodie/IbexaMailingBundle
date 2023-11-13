<?php



declare(strict_types=1);

namespace CodeRhapsodie\IbexaMailingBundle\Controller\Admin;

use CodeRhapsodie\IbexaMailingBundle\Entity\Broadcast;
use CodeRhapsodie\IbexaMailingBundle\Entity\Mailing;
use CodeRhapsodie\IbexaMailingBundle\Entity\MailingList;
use CodeRhapsodie\IbexaMailingBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class DashboardController
{
    /**
     * @Route("/", name="ibexamailing_dashboard_index")
     * @Template()
     *
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager): array
    {
        $repoBroadcast = $entityManager->getRepository(Broadcast::class);
        $repoUsers = $entityManager->getRepository(User::class);
        $repoMailings = $entityManager->getRepository(Mailing::class);

        return [
            'broadcasts' => $repoBroadcast->findLastBroadcasts(5),
            'mailings' => $repoMailings->findLastUpdated(5),
            'users' => $repoUsers->findLastUpdated(5),
        ];
    }

    /**
     * @Route("/search/autocomplete", name="ibexamailing_dashboard_search_autocomplete")
     */
    public function autocompleteSearchAction(
        Request $request,
        RouterInterface $router,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse('Not Authorized', 403);
        }

        $query = $request->query->get('query');

        $repo = $entityManager->getRepository(User::class);
        $users = $repo->findByFilters(['query' => $query]);

        $userResults = array_map(
            function (User $user) use ($router) {
                $userName = trim($user->getFirstName().' '.$user->getLastName());
                if ('' === $userName) {
                    $userName = $user->getEmail();
                }

                return [
                    'value' => $userName,
                    'data' => $router->generate('ibexamailing_user_show', ['user' => $user->getId()]),
                ];
            },
            $users
        );

        $repo = $entityManager->getRepository(MailingList::class);
        $mailingLists = $repo->findByFilters(['query' => $query]);
        $mailingListResults = array_map(
            function (MailingList $mailingList) use ($router) {
                return [
                    'value' => trim($mailingList->getName()),
                    'data' => $router->generate(
                        'ibexamailing_mailinglist_show',
                        ['mailingList' => $mailingList->getId()]
                    ),
                ];
            },
            $mailingLists
        );

        return new JsonResponse(['suggestions' => $userResults + $mailingListResults]);
    }
}
