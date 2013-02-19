Ivan1986SubrouteBundle
===================

Extension for multiple rourets

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
