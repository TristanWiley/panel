<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\SendCommandRequest;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class CommandController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonCommandRepository
     */
    private $repository;

    /**
     * CommandController constructor.
     *
     * @param \Pterodactyl\Repositories\Wings\DaemonCommandRepository $repository
     */
    public function __construct(DaemonCommandRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Send a command to a running server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\SendCommandRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function index(SendCommandRequest $request, Server $server): Response
    {
        try {
            $this->repository->setServer($server)->send($request->input('command'));
        } catch (RequestException $exception) {
            if ($exception instanceof BadResponseException) {
                if (
                    $exception->getResponse() instanceof ResponseInterface
                    && $exception->getResponse()->getStatusCode() === Response::HTTP_BAD_GATEWAY
                ) {
                    throw new HttpException(
                        Response::HTTP_BAD_GATEWAY, 'Server must be online in order to send commands.', $exception
                    );
                }
            }

            throw new DaemonConnectionException($exception);
        }

        return $this->returnNoContent();
    }
}
