# Adios vs Laravel

When choosing a framework for your new application, you might come across many different
options. Laravel is an open-source PHP web framework that provides robust and elegant syntax
to make many different development tasks easier for developers. It also comes with many
built-in features and tools which make it great for building many different web applications.
In this test, we will take a look at how does it compare to ADIOS when building a CRM app.

## Comparison results

| ADIOS                                                                                         | Laravel                                                                                                                                    |
|-----------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| Can be built easily using a json file                                                         | Requires additional configuration and libraries to reach the functionality which ADIOS offers out of the box                               |
| Developed with CRM in mind                                                                    | Built with focus on broad usage in diverse applications                                                                                    |
| Folder structure is intuitive and easy to understand (1 main directory with 6 folders inside) | Folders are complexely structured to make it better for developing large applications (2 main directories with 22 folders in total inside) |
| 0.068 ms per request                                                                          | 12.223 ms per request                                                                                                                      |

When building an identical CRM applications with both of these frameworks, ADIOS takes up only 14,5 MB (14,1 MB
dependencies) of space, while Laravel 10 needs 240 MB (150,3 MB dependencies). Website speed per request was measured by
ApacheBench.

### ADIOS project

When building the ADIOS project, we took the [11-simple-crm.json](../Prototype/examples/11-simple-crm.json)
and built the project according to the instructions in [getting-started.md](../getting-started.md).
This installation usually takes only a few minutes even with editing the installation script to take our own prototype
json as input. If you factor in building your own prototype, the time it takes until your CRM app is up and running may
vary based on the complexity of the prototype but still there's no need to touch any code.

The prototype mentioned above builds an application with a dashboard, a calendar, settings menu
and tables for customers, bids, products and product categories. These of course are automatically
connected with each other and their insert/edit forms are easily customized just by
editing the prototype json.

### Laravel project

When building an identical Laravel project, you may go through the Laravel Documentation to
install and run your project as soon as possible. However, basic Laravel projects don't feature
anything else other than a skeleton for your new application, which you can either code yourself
or look at some libraries that might do it for you. For the purposes of this test, we chose
Laravel Backpack for basic CRUD dashboard generation. As neither Laravel nor Backpack do support generating project
files
based on a Json, we still needed to code our data models and migrations ourselves.

After we have successfully created our models, specified relationships between them and
finished our model migrations, we may now create the dashboard of our app by running a few
Laravel Backpack commands. This process is however noticeably more time-consuming and there is more
room for error. Additionally, without further coding you can not reach the same functionality,
which is possible in ADIOS, such as customized forms, sidebar etc.

### Conclusion

Laravel is a great PHP framework and it really shines when it comes to building large-scale web applications.
However, this comes at the cost of it being also bigger and more complex. ADIOS on the other
hand was developed specifically for use in CRM applications. Its build process is straight-forward
and does many things for you because it knows what you will be using it for. It's especially great when it comes to
speed and project size. ADIOS only contains those file, which are important for CRM use-cases, so anything else is left
out.

