import {
  List,
  Datagrid,
  TextField,
  EmailField,
  Edit,
  SimpleForm,
  TextInput,
  Create,
  Show,
  SimpleShowLayout,
  ArrayField,
  ChipField,
  SingleFieldList,
  DateField,
  EditButton,
  DeleteButton,
  ShowButton,
  SelectInput,
  CheckboxGroupInput,
} from 'react-admin';

const userFilters = [
  <TextInput source="q" label="Search" alwaysOn />,
  <TextInput source="name" />,
  <TextInput source="email" />,
  <SelectInput source="roles" choices={[
    { id: 'ROLE_USER', name: 'User' },
    { id: 'ROLE_ADMIN', name: 'Admin' },
    { id: 'ROLE_MANAGER', name: 'Manager' },
  ]} />,
];

export const UserList = () => (
  <List filters={userFilters}>
    <Datagrid>
      <TextField source="id" />
      <TextField source="name" />
      <EmailField source="email" />
      <ArrayField source="roles">
        <SingleFieldList>
          <ChipField source="" />
        </SingleFieldList>
      </ArrayField>
      <DateField source="createdAt" />
      <ShowButton />
      <EditButton />
      <DeleteButton />
    </Datagrid>
  </List>
);

export const UserEdit = () => (
  <Edit>
    <SimpleForm>
      <TextInput source="name" required />
      <TextInput source="email" type="email" required />
      <CheckboxGroupInput source="roles" choices={[
        { id: 'ROLE_USER', name: 'User' },
        { id: 'ROLE_ADMIN', name: 'Admin' },
        { id: 'ROLE_MANAGER', name: 'Manager' },
      ]} />
    </SimpleForm>
  </Edit>
);

export const UserCreate = () => (
  <Create>
    <SimpleForm>
      <TextInput source="name" required />
      <TextInput source="email" type="email" required />
      <CheckboxGroupInput source="roles" choices={[
        { id: 'ROLE_USER', name: 'User' },
        { id: 'ROLE_ADMIN', name: 'Admin' },
        { id: 'ROLE_MANAGER', name: 'Manager' },
      ]} defaultValue={['ROLE_USER']} />
    </SimpleForm>
  </Create>
);

export const UserShow = () => (
  <Show>
    <SimpleShowLayout>
      <TextField source="id" />
      <TextField source="name" />
      <EmailField source="email" />
      <ArrayField source="roles">
        <SingleFieldList>
          <ChipField source="" />
        </SingleFieldList>
      </ArrayField>
      <DateField source="createdAt" />
    </SimpleShowLayout>
  </Show>
);