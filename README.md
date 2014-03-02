## Outbrain Propagator

**Propagator** is a schema & data deployment tool that works on a multi-everything topology: 

  * Multi-server: push your schema & data changes to multiple instances in parallel
  * Multi-role: different servers have different schemas
  * Multi-environment: recognizes the differences between development, QA, build & production servers
  * Multi-technology: supports MySQL, Hive (Cassandra on the TODO list)
  * Multi-user: allows users authenticated and audited access
  * Multi-planetary: TODO

It makes for a centralized deployment control, allowing for tracking, auditing
and management of deployed scripts.

It answers such questions as "Who added column 'x' to table 't' and when?",
"Was that column added to the build & test servers?"; "It's not there; was
there a failure? What was the failure?". It provides with: "OK, let's deploy
it on all machines"; "There was some error and it's fixed now. Let's deploy
again on this paritular instance"; "We already deployed this manually; so
let's just mark it as 'deployed'".

_Propagator_ is developed at [Outbrain](http://www.outbrain.com) to answer for
the difficulty in managing schema changes made by dozens of developers on a
multi-everything topology in continuous delivery.

_Propagator_ is released as open source under the [Apache 2.0
license](http://www.apache.org/licenses/LICENSE-2.0). Find project code in
GitHub: <https://github.com/outbrain/propagator>

Developed by Shlomi Noach.

### Documentation: installation, setup, usage

Please read the [Propagator Manual](MANUAL.md)


