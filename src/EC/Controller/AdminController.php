<?php

namespace EC\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolation;

class AdminController
{
    public function usersAction(Request $request, Application $app)
    {
        return $app['twig']->render(
            'admin/users.twig',
            array('users' => $app['datalayer.users']())
        );
    }

    protected function validatePassword(Request $request, Application $app)
    {
        $constraint = new Assert\Collection(
            array(
                'new_password' => array(
                    new Assert\Length(
                        array('max' => 100)
                    ),
                ),
                'new_password_confirm' => array(
                    new Assert\Length(
                        array('max' => 100)
                    ),
                    new Assert\EqualTo(
                        array(
                            'value'    => $request->get('new_password'), // must match new password
                            'message'  => 'The new password does not match the confirmation password!'
                        )
                    )
                )
            )
        );

        return $app['validator']->validateValue(
            array(
                'new_password'          => $request->get('new_password'),
                'new_password_confirm'  => $request->get('new_password_confirm')
            ),
            $constraint
        );
    }

    public function changeUserAction(Request $request, Application $app)
    {
        $validation = array(
            'errors' => new ConstraintViolationList(),
            'success' => true
        );

        if ($request->get('new_password') != null || $request->get('new_password_confirm') != null) { // Password does not always need to update when saving profile
            $validation['errors'] = $this->validatePassword($request, $app);
            $validation['success'] = ($validation['errors']->count() == null);

            if ($validation['success']) { // Update the profile
                $app['datalayer.updatepassword'](
                    $request->get('userid'),
                    $app['security.encoder.digest']->encodePassword($request->get('new_password'), null)
                );
            }
        }

        $devices = array(
            'user'      => $app['devices.getzipcodes']($app['devices.list'](true, $request->get('userid'))), // user devices (zipcodes)
            'all'       => $app['devices.list.all'](true), // all devices (zipcodes)
            'form'      => $request->get('devices') ? $request->get('devices') : array(), // submitted devices by form (zipcodes)
            'added'     => null,
            'removed'   => null,
        );

        $devices['added'] = array_diff($devices['form'], $devices['user']); // devices that should be added
        $devices['removed'] = array_diff($devices['user'], $devices['form']); // devices that should be removed

        if ($request->get('devices') != null || ($devices['user'] != null && $request->get('devices') == null)) { // Update when new devices are added, deleted OR when the last device is deleted
            foreach ($devices['added'] as $device) { // Check if each device that is about to be added exists
                $validation['success'] = in_array($device, $devices['all']);

                if (!$validation['success']) {
                    $validation['errors']->add(
                        new ConstraintViolation('Device does not exist: ' . $device, null, array(), null, null, null)
                    ); // Add error to list
                }
            }

            if ($validation['success']) {
                $app['devices.update']( // update device list for user, will add or remove any devices if needed
                    $request->get('userid'),
                    $app['devices.getids']($devices['added']),
                    $app['devices.getids']($devices['removed'])
                );
            }
        }

        return $this->viewUserAction($request, $app, $validation['errors'], $validation['success']); // re-render the page and show if the profile was updated
    }

    public function viewUserAction(Request $request, Application $app, $errors = null, $profileUpdated = null)
    {
        return $app['twig']->render(
            'admin/viewuser.twig',
            array(
                'user'           => $app['datalayer.user']($request->get('userid')),
                'user_devices'   => $app['devices.list'](true, $request->get('userid')),
                'all_devices'    => json_encode($app['devices.list.all'](true)),
                'errors'         => $errors,
                'profileupdated' => $profileUpdated
            )
        );
    }
}