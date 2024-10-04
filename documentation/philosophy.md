---
hide:
    - navigation
    - toc
---

# Philosophy

Most migration tools allow to define _up_ and _down_ migrations. When something goes wrong while migrating up it then rollbacks to the previous state by applying corresponding _down_ migration.

!!! note ""
    This library purposefully **DOES NOT** support this mechanism. You can only go _up_!

Allowing to specify _down_ migrations gives you a false sense of security that you can always rollback to a known state.

This is most of the time misleading because if your migration failed it means you didn't think of all possible scenarii. At this point your app is in an unknown state. Trying to apply a rollback from this uncertain point will most likely increase the problem.

!!! note ""
    Instead of relying on this false sense of security you should maximize your ability to react quickly to a problem by once again moving forward by writing a new _up_ migration once the unknown state is understood.

    You can achieve this via quality assurances:

    - use Pull Requests and review them
    - write automated tests and run them in a CI
    - automate the whole deploy process of your app

In the case you really want to write _down_ migrations you can still build such system on top of this library. But you'd probably be better off looking for another tool.
