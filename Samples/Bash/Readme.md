# Bash scripting

This was one of my first bash scripts to automate the most basic yet tedious task:

Clone the production/RC server database and mount it locally in order to get 1:1 local development environment

The script makes the following assumptions:

- You have a local SSH private key file configured either in ~/.ssh/config or ~/.ssh/id_rsa
- Your target server has your public key already loaded
- Your local user on target has access to database you want to clone
- Yo have the command 'pv' installed on your host
- Target server has sufficient space to dump the database AND store the compressed version at the same time

Sample output

![alt text](https://github.com/Rodricity/resume/blob/master/Samples/Bash/console_output.png "Output image file")
