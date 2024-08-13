# Email disaster recovery
I forgot to backup my server email repository and i've deleted the folder zith all my emails.

# The process
I've used photorec to recover all the deleted files.
all the emails are now in different recup.dir folders with random names and extensions.

# The tool
I've written this little php script to identify valid emails, identify the mailbox and the date.
This script parse all the mime files and upload through IMAP into the mailbox.
