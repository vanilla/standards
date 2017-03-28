## Dependancy Management

Front end dependancies are managed using `yarn`. Installation instructions for yarn are available [here](https://yarnpkg.com/en/docs/install).

[Why Yarn?](https://scotch.io/tutorials/yarn-package-manager-an-improvement-over-npm)

#### Summary

Yarn is a replacement to the npm CLI tool. It still uses all the same sources at [npmjs.com](npmjs.com), but boasts some extra features, such as locking down subdependancy versions and interactive upgrades.

#### Equivalents to NPM commands

`npm init`:    `yarn init`

`npm install`: `yarn install`

`npm install --save [package]`: `yarn add [package]`

`npm install --save-dev [package]`: `yarn add --dev [package]`

[Migrating from NPM guiide](https://yarnpkg.com/lang/en/docs/migrating-from-npm/)

#### `yarn.lock`

The yarn.lock file is quite similar to other package managers' lock files, especially Rust's Cargo package manager, which has Cargo.lock. The idea of these lock files is to represent a consistent set of packages that should always work.

npm stores dependency ranges in the package.json file, which means that when someone installs your package, they might get a different set of dependencies to you, since you might be running outdated packages (although they still satisfy the dependency range you specified). Take, for example, someone who has specified the dependency "foo": "^1.0.0". They might have actually installed foo v1.0.1, because that was the latest when they ran npm install, but later on, someone installs your package and gets the dependency foo v1.1.0. This might break something unexpectedly, which can be avoided if you have a yarn.lock file which guarantees **consistent package resolution**.

As for comparison with npm shrinkwrap, the documentation explains it very clearly:

> It’s similar to npm’s npm-shrinkwrap.json, however it’s not lossy and it creates reproducible results.
The documentation also advises committing yarn.lock to your repositories, if you're not already doing this, so you can reap the benefits of consistent and reproducible package resolution. This question also explains further why you should do this.

The lossy behaviour of npm shrinkwrap is due to the non-deterministic algorithms used by npm itself; as stated in the comments of another answer, npm shrinkwrap > npm install > npm shrinkwrap is not guaranteed to produce the same output as just shrinkwrapping once, whereas Yarn explicitly uses "an install algorithm that is deterministic and reliable".

