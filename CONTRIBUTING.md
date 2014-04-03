# How to contribute

We appreciate that you are interested in contributing towards to development of our WordPress plugin. Since, WordPress plugin development in an open source environment the same as other projects there are few guidelines that are necessary for you to follow.

## Getting Started

- Make sure you have a [GitHub account](https://github.com/signup/free).
- Create an issue (if it already doesn't exists)
  - Describe steps to follow to reproduce the issue
  - Mention the version of the plugin you are using
- Fork the repository

## Making changes

- Create a topic branch from where you want to base your work.
  - Usually the master branch.
  - Only target release branches if you are certain your fix must be on that branch.
- Make rational commits.
- Check for unnecessary whitespace.
- Submit a Pull Request (PR).

## How changes are committed to master?

Since we are working on WordPress plugin repository, it is necessary for all commits to have a `git-svn-id` with them. This is why before committing any changes to the master, the plugin maintainer will first commit the changes on the SVN repository and then push the changes on GitHub.