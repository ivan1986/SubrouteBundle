=============
Quick Summary
=============

What It Do
----------

Allows many routing files called for controllers
For example, we have::

    /{user}/albums/{album}/list
    /{user}/albums/{album}/add
    /{user}/albums/{album}/delete/{id}
    /{user}/albums/{album}/edit/{id}
    /{user}/albums/{album}/show/{id}

A problem::

    /{user}/albums/{album}/

one for all this urls, and logic one for all urls.

Solution::

    Routing(/{user}/albums/{album}/{slug})
    run Controller
    continue Routing slug whis $this->get('subrouter')->route('album', 'slug');
    run other Controller
    
Installation
------------

Recommended instalation is over Composer::

    // composer.json
    {
        // ...
        require: {
            // ...
            "ivan1986/subroute": "dev-master"
        }
    }

Configuration and usage
-----------------------

Call subrouting from controller::

    $this->get('subrouter')->route('router-name', 'parameter-for-continue-routing');

return Response object
    
Allows you to define roter files in a config file::

    subroute:
        album: "%kernel.root_dir%/config/routing/album.yml"
        apps: "%kernel.root_dir%/config/routing/apps.yml"

Generate a url in current router path::

    $url = $this->get('subroute')->generateMyUrl('edit', array('id' => $id))

Generate a url whis suburl::

    $url = $this->generateUrl('albums', array('user' => $user, 'album' => $album, 'slug' => $this->get('subroute')->generateSubUrl('album', 'edit', array('id' => $id)) ));

Twig Extensions::

    {{ mysubpath('edit', {'id':id}) }}
    {{ path('album', { 'user': user, 'album': album, 'slug' : subpath('album', 'edit', {'id':id})}) }}
