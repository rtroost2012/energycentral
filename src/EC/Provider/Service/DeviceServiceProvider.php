<?php

namespace EC\Provider\Service;

use Silex\Application;
use Silex\ServiceProviderInterface;

class DeviceServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        /*
         * Update the device access table for a specific user.
         * The function adds new devices or removes devices that the user should no longer have access to.
         */
        $app['devices.update'] = $app->protect(
            function ($userId, array $addedDevices, array $removedDevices) use ($app) {
                /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                $queryBuilder = $app['db']->createQueryBuilder();

                foreach ($addedDevices as $deviceId) { // add new devices
                    $queryBuilder
                        ->insert('devaccess', 'dev')
                        ->values(
                            array(
                                'deviceid'  => $deviceId,
                                'userid'    => $userId
                            )
                        )
                        ->execute();
                }

                foreach ($removedDevices as $deviceId) { // remove old devices if needed
                    $queryBuilder
                        ->delete('devaccess')
                        ->where('deviceid = :deviceid AND userid = :userid')
                        ->setParameters(
                            array(
                                'deviceid' => $deviceId,
                                'userid' => $userId
                            )
                        )
                        ->execute();
                }
            }
        );

        $app['devices.update_accepted'] = $app->protect(
            function ($deviceId, $accepted = false) use ($app) {
                /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                $queryBuilder = $app['db']->createQueryBuilder();
                $queryBuilder
                    ->update('device', 'dev')
                    ->set('accepted', $accepted)
                    ->where('deviceid = :deviceid')
                    ->setParameter('deviceid', $deviceId);

                return $queryBuilder->execute();
            }
        );

        $app['devices.count'] = $app->protect(
            function ($userId = null) use ($app) {
                /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                $queryBuilder = $app['db']->createQueryBuilder()
                    ->select('COUNT(*)');

                if ($userId != null) {
                    $queryBuilder
                        ->from('devaccess', 'dev')
                        ->where('dev.userid = :userid')
                        ->setParameter('userid', $userId);
                } else {
                    $queryBuilder->from('device', 'dev');
                }

                $stmt = $queryBuilder->execute();
                return $stmt->fetchColumn();
            }
        );

        /*
         * Update the device access table.
         * The function adds new users or removes users from a device.
         */
        $app['devices.update_users'] = $app->protect(
            function ($deviceId, array $addedUsers, array $removedUsers) use ($app) {
                /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                $queryBuilder = $app['db']->createQueryBuilder();

                foreach ($addedUsers as $userId) { // add new users
                    $queryBuilder
                        ->insert('devaccess', 'dev')
                        ->values(
                            array(
                                'deviceid'  => $deviceId,
                                'userid'    => $userId
                            )
                        )
                        ->execute();
                }

                foreach ($removedUsers as $userId) { // remove old users if needed
                    $queryBuilder
                        ->delete('devaccess')
                        ->where('deviceid = :deviceid AND userid = :userid')
                        ->setParameters(
                            array(
                                'deviceid' => $deviceId,
                                'userid' => $userId
                            )
                        )
                        ->execute();
                }
            }
        );

        /*
         * Extracts the name from a device array
         */
        $app['devices.getnames'] = $app->protect(
            function (array $devices) {
                array_walk(
                    $devices,
                    function (&$item) {
                        $item = $item['name'];
                    }
                );
                return $devices;
            }
        );

        /*
         * Returns the id for every device in the array
         */
        $app['devices.getids'] = $app->protect(
            function (array $devices) use ($app) {
                $deviceIds = array();

                foreach ($devices as $device) {
                    /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                    $queryBuilder = $app['db']->createQueryBuilder()
                        ->select('deviceid')
                        ->from('device', 'dev')
                        ->where('name = :name')
                        ->setParameter('name', $device);

                    $stmt = $queryBuilder->execute();
                    $deviceIds[$device] = $stmt->fetchColumn();
                }
                return $deviceIds;
            }
        );

        /*
         * List all devices a user has access to, including the name if needed
         */
        $app['devices.list'] = $app->protect(
            function ($withDetails = false, $userId = null, $offset = null, $limit = null) use ($app) {
                /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                $queryBuilder = $app['db']->createQueryBuilder();

                if ($withDetails) { // include name and house number?
                    $queryBuilder
                        ->select('dev.*')
                        ->from('devaccess', 'ac')
                        ->innerJoin('ac', 'device', 'dev', 'ac.deviceid = dev.deviceid');
                } else {
                    $queryBuilder
                        ->select('deviceid')
                        ->from('devaccess', 'dev');
                }

                if ($offset != null || $limit != null) {
                    $queryBuilder
                        ->setFirstResult($offset)
                        ->setMaxResults($limit);
                }

                $queryBuilder
                    ->where('userid = :userid')
                    ->setParameter('userid', $userId == null ? $app->user()->getId() : $userId);

                $stmt = $queryBuilder->execute();
                return $stmt->fetchAll();
            }
        );

        /*
         * List all users that belong to a specific device ID
         */
        $app['devices.list_users'] = $app->protect(
            function ($deviceId = null) use ($app) {
                /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                $queryBuilder = $app['db']->createQueryBuilder()
                    ->select('u.username')
                    ->from('devaccess', 'a')
                    ->innerJoin('a', 'user', 'u', 'a.userid = u.userid')
                    ->where('a.deviceid = :deviceid')
                    ->setParameter('deviceid', $deviceId);

                $stmt = $queryBuilder->execute();
                return $stmt->fetchAll(\PDO::FETCH_COLUMN);
            }
        );

        /*
         * List all devices, including name if needed
         */
        $app['devices.list.all'] = $app->protect(
            function ($nameOnly = false, $offset = null, $limit = null) use ($app) {
                /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                $queryBuilder = $app['db']->createQueryBuilder()
                    ->select($nameOnly ? 'name' : '*')
                    ->from('device', 'dev');

                if ($offset != null || $limit != null) {
                    $queryBuilder
                        ->setFirstResult($offset)
                        ->setMaxResults($limit);
                }

                $stmt = $queryBuilder->execute();
                return $stmt->fetchAll($nameOnly ? \PDO::FETCH_COLUMN : \PDO::FETCH_ASSOC);
            }
        );

        /*
         * Checks if a user has access to a specific device
         */
        $app['devices.hasaccess'] = $app->protect(
            function ($deviceId, $userId = null) use ($app) {
                if (!$app['centralmode']) { // not needed when running in local mode
                    return true;
                }
                /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
                $queryBuilder = $app['db']->createQueryBuilder()
                    ->select('ac.deviceid')
                    ->from('devaccess', 'ac')
                    ->where('userid = :userid')
                    ->setParameter('userid', $userId == null ? $app->user()->getId() : $userId);

                $stmt = $queryBuilder->execute();
                return in_array($deviceId, $stmt->fetchAll(\PDO::FETCH_COLUMN));
            }
        );
    }

    public function boot(Application $app)
    {
    }
}